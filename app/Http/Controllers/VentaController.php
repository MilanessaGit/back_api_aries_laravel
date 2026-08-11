<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\Salida;
use App\Models\Lote;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    public function index()
    {
        $ventas = Venta::with('cliente', 'lotes')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($ventas);
    }

    /**
     * Registra una venta aplicando FIFO automáticamente.
     *
     * Reglas de negocio:
     * - DIRECTA: pago completo, adelanto = 0 y saldo = 0.
     * - RESERVA / CONTRATO: un adelanto inicial y un único saldo pendiente.
     * - El precio de venta se toma de productos.precio_sugerido.
     * - Los lotes se consumen por fecha_ingreso ASC, id ASC.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|numeric|exists:clientes,id',
            'empleado_id' => 'required|numeric|exists:empleados,id',
            'tipo_venta' => 'required|in:DIRECTA,RESERVA,CONTRATO',
            'adelanto' => 'nullable|numeric|min:0',
            'fecha_entrega' => 'nullable|date',
            'observaciones' => 'nullable|string|max:1000',

            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|integer|distinct|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $lotesPorProducto = [];
            $preciosPorProducto = [];
            $totalVenta = 0.0;

            /*
             * PASO 1: validar productos, precio de venta y stock.
             */
            foreach ($request->productos as $item) {
                $productoId = (int) $item['producto_id'];
                $cantidadSolicitada = (int) $item['cantidad'];

                $producto = Producto::findOrFail($productoId);
                $precioVenta = (float) $producto->precio_sugerido;

                if ($precioVenta <= 0) {
                    throw new \Exception(
                        "El producto {$producto->nombre} no tiene un precio de venta válido."
                    );
                }

                $lotes = Lote::where('producto_id', $productoId)
                    ->where('cantidad_actual', '>', 0)
                    ->orderBy('fecha_ingreso', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $stockDisponible = (int) $lotes->sum('cantidad_actual');

                if ($stockDisponible < $cantidadSolicitada) {
                    throw new \Exception(
                        "Stock insuficiente para {$producto->nombre}. " .
                        "Disponible: {$stockDisponible}, Solicitado: {$cantidadSolicitada}"
                    );
                }

                $lotesPorProducto[$productoId] = $lotes;
                $preciosPorProducto[$productoId] = $precioVenta;
                $totalVenta += $cantidadSolicitada * $precioVenta;
            }

            /*
             * PASO 2: aplicar reglas de pago según el tipo de venta.
             */
            $tipoVenta = strtoupper($request->tipo_venta);
            $adelanto = 0.0;
            $saldo = 0.0;

            if ($tipoVenta === 'DIRECTA') {
                // La venta directa se paga completamente en una sola operación.
                $adelanto = 0.0;
                $saldo = 0.0;
            } else {
                // Reserva y Contrato requieren un adelanto inicial.
                $adelanto = (float) ($request->adelanto ?? 0);

                if ($adelanto <= 0) {
                    throw new \Exception(
                        'La Reserva y el Contrato requieren un adelanto mayor a 0.'
                    );
                }

                if ($adelanto >= $totalVenta) {
                    throw new \Exception(
                        'El adelanto debe ser menor al total para que exista un pago final pendiente.'
                    );
                }

                $saldo = $totalVenta - $adelanto;
            }

            /*
             * PASO 3: registrar la venta.
             */
            $venta = new Venta();
            $venta->cliente_id = (int) $request->cliente_id;
            $venta->empleado_id = (int) $request->empleado_id;
            $venta->codigo_venta = Venta::generarCodigoVenta();
            $venta->tipo_venta = $tipoVenta;
            $venta->fecha_venta = now();
            $venta->total = $totalVenta;
            $venta->adelanto = $adelanto;
            $venta->saldo = $saldo;
            $venta->fecha_entrega = $request->fecha_entrega ?? null;
            $venta->observaciones = $request->observaciones ?? null;
            $venta->estado = 1;
            $venta->save();

            /*
             * PASO 4: registrar la salida asociada.
             */
            $salida = new Salida();
            $salida->codigo_salida = Salida::generarCodigoSalida();
            $salida->fecha = now();
            $salida->tipo = 1;
            $salida->venta_id = $venta->id;
            $salida->aprobado_por = (int) $request->empleado_id;
            $salida->save();

            /*
             * PASO 5: aplicar FIFO producto por producto.
             */
            foreach ($request->productos as $item) {
                $productoId = (int) $item['producto_id'];
                $cantidadPendiente = (int) $item['cantidad'];
                $precioVenta = $preciosPorProducto[$productoId];
                $lotes = $lotesPorProducto[$productoId];

                foreach ($lotes as $lote) {
                    if ($cantidadPendiente <= 0) {
                        break;
                    }

                    $cantidadADescontar = min(
                        $cantidadPendiente,
                        (int) $lote->cantidad_actual
                    );

                    // Trazabilidad Venta -> Lote.
                    // precio_unitario guarda el precio de venta aplicado al producto.
                    $venta->lotes()->attach($lote->id, [
                        'cantidad' => $cantidadADescontar,
                        'precio_unitario' => $precioVenta,
                    ]);

                    // Trazabilidad Salida -> Lote.
                    $salida->lotes()->attach($lote->id, [
                        'cantidad' => $cantidadADescontar,
                        'observaciones' => 'Salida generada por venta ' . $venta->codigo_venta,
                    ]);

                    $lote->cantidad_actual -= $cantidadADescontar;
                    $lote->save();

                    $cantidadPendiente -= $cantidadADescontar;
                }

                if ($cantidadPendiente > 0) {
                    throw new \Exception(
                        "No fue posible completar la venta del producto ID {$productoId}."
                    );
                }
            }

            DB::commit();

            return response()->json([
                'mensaje' => 'Venta registrada correctamente aplicando FIFO',
                'data' => $venta->fresh(),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'mensaje' => 'Error al registrar la venta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $venta = Venta::with('cliente', 'lotes')->findOrFail($id);
        return response()->json($venta);
    }

    /**
     * Registra el único pago final de una Reserva o Contrato.
     *
     * No vuelve a descontar inventario y no modifica FIFO.
     * El pago final siempre corresponde exactamente al saldo pendiente.
     */
    /**
     * Actualiza acciones puntuales de la venta sin volver a tocar inventario.
     *
     * Acciones disponibles:
     * - PAGO_FINAL: cancela el único saldo pendiente de Reserva/Contrato.
     * - MARCAR_ENTREGADA: cambia estado 1 -> 2 cuando la venta ya está pagada.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'accion' => 'required|in:PAGO_FINAL,MARCAR_ENTREGADA',
        ]);

        DB::beginTransaction();

        try {
            $venta = Venta::where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $venta->estado === 3) {
                throw new \Exception('No se puede modificar una venta cancelada.');
            }

            if ($request->accion === 'PAGO_FINAL') {
                $tipoVenta = strtoupper((string) $venta->tipo_venta);

                if (!in_array($tipoVenta, ['RESERVA', 'CONTRATO'], true)) {
                    throw new \Exception(
                        'El pago final solo corresponde a ventas de tipo Reserva o Contrato.'
                    );
                }

                $saldoPendiente = (float) $venta->saldo;

                if ($saldoPendiente <= 0) {
                    throw new \Exception('La venta no tiene saldo pendiente.');
                }

                // Existe un único pago final: se cancela todo el saldo pendiente.
                $venta->saldo = 0;
                $venta->save();

                DB::commit();

                return response()->json([
                    'mensaje' => 'Pago final registrado correctamente',
                    'monto_pago_final' => $saldoPendiente,
                    'data' => $venta->fresh(['cliente', 'lotes']),
                ]);
            }

            if ($request->accion === 'MARCAR_ENTREGADA') {
                if ((int) $venta->estado === 2) {
                    throw new \Exception('La venta ya se encuentra completada/entregada.');
                }

                if ((float) $venta->saldo > 0) {
                    throw new \Exception(
                        'No se puede marcar como entregada una venta con saldo pendiente.'
                    );
                }

                // Estado de venta: 1 = pendiente de entrega, 2 = completada/entregada.
                $venta->estado = 2;
                $venta->save();

                DB::commit();

                return response()->json([
                    'mensaje' => 'Venta marcada como entregada correctamente',
                    'data' => $venta->fresh(['cliente', 'lotes']),
                ]);
            }

            throw new \Exception('Acción no soportada.');
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'mensaje' => 'Error al actualizar la venta',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy($id)
    {
        // No se modifica en esta fase.
    }
}
