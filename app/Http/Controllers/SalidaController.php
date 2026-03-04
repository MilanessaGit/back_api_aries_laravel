<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SalidaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // /api/salida?page=2&limit=10$q=1223&orderby=id
        //$page = $request->page; // ya captura por si solo sin guardarlo en una variable
        $limit = $request->limit?$request->limit:10;
        $q = $request->q; //valor de busqueda
        $orderby = $request->orderby?$request->orderby:'id';

        if($q){
            $salidas = Salida::where('codigo_salida','like',"%$q%")
            ->orderBy($orderby,'desc')
            ->paginate($limit);

        }else{
            $salidas = Salida::orderBy($orderby,'desc')->paginate($limit);
        }

        return response()->json($salidas, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // validar
        $request->validate([
            'codigo_salida' => 'required|unique:salidas',
            'fecha' => 'required|date',
            'total' => 'nullable|numeric',
            'tipo' => 'nullable|in:venta,consumo',
            'observaciones' => 'nullable|string',
            //'cantidad' => 'required|numeric',
            'empleado_id' => 'required' //|exists:empleados,id'
        ]);

        // guardar
        $salida = new Salida();
        $salida->codigo_salida = $request->codigo_salida;
        $salida->fecha = $request->fecha;
        $salida->total = $request->total;
        $salida->tipo = $request->tipo;
        $salida->observaciones = $request->observaciones;
        $salida->empleado_id = $request->empleado_id;
        $salida->save();

        return response()->json(['message' => 'Salida registrada exitosamente', 'data' => $salida]);

        
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
