<?php

namespace App\Http\Controllers;

use App\Models\Venta;
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
        DB::beginTransaction();
        
        //validar
        $request->validate([
            "cliente_id" => "required",
        ]);

        try{
        //registrar nueva venta (PENDIENTE)
        $venta = new Venta();
        // asignar cliente a la venta
        $venta->cliente_id = $request->cliente_id['id'];
        $venta->empleado_id = $request->empleado_id.'1'; 
        $venta->codigo_venta = Venta::generarCodigoVenta();
        $venta->fecha = date('Y-m-d H:i:s');
        $venta->total = $request->total.'0';
        $venta->estado = '1'; //1: activo, 0:anulado
        $venta->observaciones = $request->observaciones;
        $venta->save();
        /*
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
            foreach ($lotes as $lot) {
                $id = $lot["id"];
                $cantidad = $lot["cantidad"];
                $precio_unitario = 0.0; // Valor por defecto  

                //EJ:  $user->roles()->attach($roleId, ['expires' => $expires]);
                $venta->lotes()->attach($id, ['cantidad' => $cantidad, 'precio_unitario' => $precio_unitario]);
               // $venta->lotes()->attach($id, ['precio_unitario' => $precio_unitario]);
            }
            //return $venta;

            // actualizar estado //  COMPLETADO
            $venta->estado = 2;
            // guardarmos cambios
            $venta->update();
            // retornamos respuesta

            DB::commit();
            // all good
            return response()->json(["mensaje" => "Venta Registrada", "data" => $venta], 200);
        } catch (\Exception $e) {
            DB::rollback();
            // something went wrong
            return response()->json(["mensaje" => "Error al registrar la venta", "error" => $e->getMessage()], 500);
        }


        return response()->json($venta, 201);
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
