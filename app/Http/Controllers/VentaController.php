<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Salida;
use App\Models\Lote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ventas = Venta::with('cliente', 'lotes')
            ->orderBy('id', 'asc')
            ->paginate(10);

        return response()->json($ventas);
    }

    /**
     * Store a newly created resource in storage.
     *
     * La venta recibe PRODUCTOS y CANTIDADES.
     * Laravel selecciona automáticamente los lotes aplicando FIFO:
     * fecha_ingreso ASC y, en caso de empate, id ASC.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|numeric|exists:clientes,id',
            'empleado_id' => 'required|numeric|exists:empleados,id',
            'tipo_venta' => 'nullable|string',

            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|integer|distinct|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {

            /*
             * PASO 1:
             * Validar stock y bloquear los lotes involucrados.
             */
            $lotesPorProducto = [];

            foreach ($request->productos as $item) {

                $productoId = (int) $item['producto_id'];
                $cantidadSolicitada = (int) $item['cantidad'];

                $lotes = Lote::where('producto_id', $productoId)
                    ->where('cantidad_actual', '>', 0)
                    ->orderBy('fecha_ingreso', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $stockDisponible = (int) $lotes->sum('cantidad_actual');

                if ($stockDisponible < $cantidadSolicitada) {
                    throw new \Exception(
                        "Stock insuficiente para el producto ID {$productoId}. " .
                        "Disponible: {$stockDisponible}, Solicitado: {$cantidadSolicitada}"
                    );
                }

                $lotesPorProducto[$productoId] = $lotes;
            }

            /*
             * PASO 2:
             * Registrar la venta.
             */
            $venta = new Venta();

            $venta->cliente_id = (int) $request->cliente_id;
            $venta->empleado_id = (int) $request->empleado_id;
            $venta->codigo_venta = Venta::generarCodigoVenta();
            $venta->tipo_venta = $request->tipo_venta ?? 'Directa';
            $venta->fecha_venta = date('Y-m-d H:i:s');

            $venta->total = 0;
            $venta->estado = 1;

            $venta->save();

            /*
             * PASO 3:
             * Registrar la salida asociada a la venta.
             */
            $salida = new Salida();

            $salida->codigo_salida = Salida::generarCodigoSalida();
            $salida->fecha = now();
            $salida->tipo = 1;
            $salida->venta_id = $venta->id;
            $salida->aprobado_por = (int) $request->empleado_id;

            $salida->save();

            /*
             * PASO 4:
             * Aplicar FIFO producto por producto.
             */
            $calculatedTotal = 0.0;

            foreach ($request->productos as $item) {

                $productoId = (int) $item['producto_id'];
                $cantidadPendiente = (int) $item['cantidad'];

                $lotes = $lotesPorProducto[$productoId];

                foreach ($lotes as $lote) {

                    if ($cantidadPendiente <= 0) {
                        break;
                    }

                    /*
                     * Determinamos cuánto sacar del lote actual.
                     */
                    $cantidadADescontar = min(
                        $cantidadPendiente,
                        (int) $lote->cantidad_actual
                    );

                    $precioUnitario = (float) $lote->costo_unitario;

                    /*
                     * Registrar trazabilidad:
                     *
                     * Venta -> Lote
                     */
                    $venta->lotes()->attach($lote->id, [
                        'cantidad' => $cantidadADescontar,
                        'precio_unitario' => $precioUnitario,
                    ]);

                    /*
                     * Registrar trazabilidad:
                     *
                     * Salida -> Lote
                     */
                    $salida->lotes()->attach($lote->id, [
                        'cantidad' => $cantidadADescontar,
                        'observaciones' =>
                            'Salida generada por venta ' .
                            $venta->codigo_venta,
                    ]);

                    /*
                     * Descontar físicamente el inventario.
                     */
                    $lote->cantidad_actual -= $cantidadADescontar;
                    $lote->save();

                    /*
                     * Por ahora conservamos el cálculo original
                     * del sistema basado en costo_unitario.
                     *
                     * El precio de venta se revisará posteriormente
                     * sin mezclarlo con FIFO.
                     */
                    $calculatedTotal +=
                        $cantidadADescontar * $precioUnitario;

                    /*
                     * Restamos lo que ya conseguimos cubrir.
                     */
                    $cantidadPendiente -= $cantidadADescontar;
                }

                /*
                 * Seguridad adicional.
                 *
                 * En condiciones normales esto nunca debería
                 * ejecutarse porque el stock ya fue validado.
                 */
                if ($cantidadPendiente > 0) {
                    throw new \Exception(
                        "No fue posible completar la venta " .
                        "del producto ID {$productoId}."
                    );
                }
            }

            /*
             * PASO 5:
             * Actualizar total de la venta.
             */
            $venta->total = $calculatedTotal;
            $venta->save();

            /*
             * Todo salió correctamente.
             */
            DB::commit();

            return response()->json([
                'mensaje' =>
                    'Venta registrada correctamente aplicando FIFO',
                'data' => $venta,
            ], 201);

        } catch (\Exception $e) {

            /*
             * Si algo falla:
             *
             * - venta
             * - salida
             * - lote_venta
             * - lote_salida
             * - cantidad_actual
             *
             * regresan al estado anterior.
             */
            DB::rollBack();

            return response()->json([
                'mensaje' => 'Error al registrar la venta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}