<?php

namespace App\Http\Controllers;

use App\Models\Venta;
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
        $ventas = Venta::with('cliente', 'lotes')->orderBy('id', 'desc')->paginate(5);

        //$ventas = Venta::orderBy('id', 'desc')->paginate(10);
        return response()->json($ventas);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validación completa
        $request->validate([
            'cliente_id' => 'required|numeric|exists:clientes,id',
            //'empleado_id' => 'required|numeric|exists:empleados,id',
            'lotes' => 'required|array|min:1',
            'lotes.*.id' => 'required|numeric|exists:lotes,id',
            'lotes.*.cantidad' => 'required|numeric|min:1',
            //'observaciones' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try{
            //registrar nueva venta (PENDIENTE)
            $venta = new Venta();
            // asignar cliente a la venta
            $venta->cliente_id = (int) $request->cliente_id;
            $venta->empleado_id =  (int) $request->empleado_id; 
            $venta->codigo_venta = Venta::generarCodigoVenta();
            $venta->fecha = date('Y-m-d H:i:s');// now()
            $venta->total = 0; //inicializamos en 0, luego se actualiza con el total de la venta
            $venta->estado = 1; // 1: activo, 0:anulado, 2:completado
            //$venta->observaciones = $request->observaciones ?? null;
            $venta->save();

            /* Esto lo que el frontend enviaría en el request para registrar una venta
            {
                cliente_id: 5,
                lotes: [
                    {lote_id: 3, cantidad: 1},
                    {lote_id: 4, cantidad: 1},
                    {lote_id: 1, cantidad: 1},
                ]
            }
            */
            // asignar Lotes// En $request llega lo que enviamos del boton del carrito
            // los atributos de la relacion muchos a muchos se asignan en el attach)    
            $lotes = $request->lotes;
            $calculatedTotal = 0.0;

                // recorremos los lotes enviados en el request del frontend y solo tienen como atributos id y cantidad, el precio_unitario lo obtenemos de la tabla lotes
                foreach ($lotes as $lot) {
                    $loteId = (int) $lot["id"];
                    $cantidadVenta = (int) $lot["cantidad"]; //cantidad que el frontend que pide (ver arriba el ejemplo del request)
                    
                    // obtener el precio unitario del lote
                    $lote = Lote::where('id', $loteId)->lockForUpdate()->firstOrFail();
                    $precioUnitario = (float) $lote->costo_unitario;

                    //verificar si el lote tiene stock suficiente
                    if ($lote->cantidad < $cantidadVenta) {
                        throw new \Exception("Stock insuficiente para el lote {$loteId}. Disponible: {$lote->cantidad}, Solicitado: {$cantidadVenta}");
                    }

                    //EJ:  $user->roles()->attach($roleId, ['expires' => $expires]);
                    
                    //Attah nos ayuda a insertar en la tabla intermedia 'venta_lote' los datos de la venta, el lote y los atributos adicionales cantidad y precio_unitario
                    $venta->lotes()->attach($loteId, [
                        'cantidad' => $cantidadVenta,// La cantidad que el frontend solicita para ese lote
                        'precio_unitario' => $precioUnitario, // ver que precio colocar al final, si el precio del lote o el precio de venta que el frontend envía, lo ideal es que el frontend envíe el precio de venta y no el precio del lote, pero por ahora lo dejamos así************

                        //'observaciones' => $lot['observaciones'] ?? null
                        ]);
                // $venta->lotes()->attach($id, ['precio_unitario' => $precio_unitario]);
                $lote->cantidad -= $cantidadVenta; // restamos la cantidad vendida al stock del lote
                $lote->save(); // guardamos los cambios en el lote
                $calculatedTotal += $cantidadVenta * $precioUnitario; // calculamos el total de la venta
                }
                //return $venta;
                // actualizar total de la venta
                    $venta->total = $calculatedTotal;
                    $venta->estado = 2; // COMPLETADO
                    $venta->save();
                // actualizar estado //  COMPLETADO
                    //  $venta->estado = 2;
                // guardarmos cambios
                    // $venta->update();
                // retornamos respuesta

                DB::commit();
                // all good
                return response()->json(["mensaje" => "Venta Registrada", "data" => $venta], 200);
        } catch (\Exception $e) {
            DB::rollback();
            // something went wrong
            return response()->json(["mensaje" => "Error al registrar la venta", "error" => $e->getMessage()], 500);
        }


        //return response()->json($venta, 201);
        
        // return response()->json(["mensaje" => "Venta Registrada"], 201);
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
