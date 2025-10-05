<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;

class VentaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $ventas = Venta::orderBy('id', 'desc')->paginate(10);
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
        //validar
        $request->validate([
            "cliente_id" => "required",
        ]);
        //registrar nueva venta (PENDIENTE)
        $venta = new Venta();
        $venta->cliente_id = $request->cliente_id;
        $venta->empleado_id = $request->empleado_id;
        $venta->codigo_venta = Venta::generarCodigoVenta();
        $venta->fecha = date('Y-m-d H:i:s');
        $venta->total = $request->total;
        $venta->estado = '1'; //1: activo, 0:anulado
        $venta->observaciones = $request->observaciones;
        $venta->save();
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
