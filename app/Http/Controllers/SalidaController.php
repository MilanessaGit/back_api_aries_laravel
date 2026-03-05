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
