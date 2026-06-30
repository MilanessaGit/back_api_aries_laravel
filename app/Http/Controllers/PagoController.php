<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PagoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() //rev N varios
    {
        $pagoos = Pago::get();
        /*$pagos = Pago::with('venta.cliente', 'venta.empleado')->orderBy('id', 'desc')->paginate(5);*/
        return response()->json($pagos, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validar datos
        $request->validate([
            'fecha_pago' => 'required|date',
            'monto' => 'required|numeric|min:0',
            'metodo_pago' => 'required|string|max:50',
            // 'observaciones' => 'nullable|string',
            //'entrada_id' => 'required|exists:entradas,id'
        ]);

        $pago = new Pago();
        $pago->fecha_pago = $request->fecha_pago;
        $pago->monto = $request->monto;
        $pago->metodo_pago = $request->metodo_pago;
        $pago->observaciones = $request->observaciones ?? null;
        $pago->entrada_id = $request->entrada_id;
        $pago->save();
        return response()->json(["message" => "Pago creado exitosamente"], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) // rev IA
    {
        $pago = Pago::with('entrada.proveedor', 'entrada.empleado')->find($id);
        if (!$pago) {
            return response()->json(["message" => "Pago no encontrado"], 404);
        }
        return response()->json($pago, 200);
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
        $pago = Pago::find($id);
        if (!$pago) {
            return response()->json(["message" => "Pago no encontrado"], 404);
        }

        $request->validate([
            'fecha_pago' => 'required|date',
            'monto' => 'required|numeric|min:0',
            'metodo_pago' => 'required|string|max:50',
            // 'observaciones' => 'nullable|string',
            //'entrada_id' => 'required|exists:entradas,id'
        ]);

        $pago->fecha_pago = $request->fecha_pago;
        $pago->monto = $request->monto;
        $pago->metodo_pago = $request->metodo_pago;
        $pago->observaciones = $request->observaciones ?? null;
        $pago->entrada_id = $request->entrada_id;
        $pago->save();
        return response()->json(["message" => "Pago actualizado exitosamente"], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $pago = Pago::find($id);
        if (!$pago) {
            return response()->json(["message" => "Pago no encontrado"], 404);
        }
        $pago->delete();
        return response()->json(["message" => "Pago eliminado exitosamente"], 200);
    }
}
