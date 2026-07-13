<?php

namespace App\Http\Controllers;

use App\Models\Salida;
use App\Models\Lote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalidaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $salidas = Salida::with('empleado', 'lotes')->orderBy('id', 'desc')->paginate(5);

        //$salidas = Salida::orderBy('id', 'desc')->paginate(10);
        return response()->json($salidas);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    

    public function store(Request $request)
    {
        $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'tipo_salida' => 'required|integer|min:1',
            'observacion' => 'nullable|string',

            //detalle en adelante
            'productos' => 'required|array|min:1', //debes ser un arreglo con al menos un elemento

            'productos.*.producto_id' => 'required|exists:productos,id', // * = cada elemento del arreglo, revisara q tenga un producto_id
            'productos.*.cantidad' => 'required|integer|min:1'
        ]);

        DB::beginTransaction();

        try {
            $salida = new Salida();
            $salida->codigo_salida = Salida::generarCodigoSalida();
            $salida->fecha = date('Y-m-d H:i:s'); //now();
            $salida->tipo = $request->tipo_salida;
            $salida->aprobado_por = $request->empleado_id;
            $salida->observaciones = $request->observaciones;
            $salida->save();

            foreach ($request->productos as $item) { // detalle
                $cantidadSolicitada = $item['cantidad'];
                $lotes = Lote::where('producto_id', $item['producto_id'])
                                ->where('cantidad_actual', '>', 0)
                                ->orderBy('fecha_ingreso', 'asc')
                                ->lockForUpdate()
                                ->get();
                // lock bloquea mientras se consulta, para evitar procesos simultaneos
                // get obtenemos la coleccion de los lotes de nuestra consulta: Osea: lote1 - 5 | lote2 - 10 | lote3 - 15
                $stockDisponible = $lotes->sum('cantidad_actual');
                //  La suma de los lotes obtenidos(del get): 5+10+15 = 30

                if ($stockDisponible < $cantidadSolicitada) {
                    throw new Exception(
                        "Stock insuficiente para el producto ID {$item['producto_id']}"
                    );
                } // Solamente verificamos que exista suficiente stock
            }

            foreach ($request->productos as $item) { //detalle
                $productoId = $item['producto_id'];
                $cantidadSolicitada = (int)$item['cantidad'];

                // Obtener todos los lotes con stock del producto
                $lotes = Lote::where('producto_id', $productoId)
                    ->where('cantidad_actual', '>', 0)
                    ->orderBy('fecha_ingreso')
                    ->lockForUpdate()
                    ->get();
                // lock ayuda a que no se modifique nada hasta terminar la transaccion
                if ($lotes->isEmpty()) {
                    throw new \Exception("El producto no tiene stock disponible.");
                }
                $cantidadPendiente = $cantidadSolicitada;
            
                foreach ($lotes as $lote) {
                    // Si ya descontamos toda la cantidad solicitada
                    if ($cantidadPendiente <= 0) {
                        break;
                    }
                    // ¿Cuánto puedo sacar de este lote?
                    $cantidadADescontar = min(
                        $cantidadPendiente,
                        $lote->cantidad_actual
                    );
                    $lote->cantidad_actual -= $cantidadADescontar;
                    $lote->save();
                    // Habran varios registros de descuentos fisicos de cada lote
                    $salida->lotes()->attach($lote->id, [
                        'cantidad' => $cantidadADescontar,
                        
                        'observaciones' => 'Salida por '.$request->tipo_salida
                    ]);
                    $cantidadPendiente -= $cantidadADescontar;
                }
                if ($cantidadPendiente > 0) {
                        throw new Exception("Stock insuficiente para completar la salida del producto.");
                }
            }
            DB::commit();
            return response()->json([
                "mensaje" => "Salida registrada correctamente",
                "data" => $salida
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            //throw $e;
            return response()->json(['mensaje'=>'Error','error'=>$e->getMessage()],500);
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
