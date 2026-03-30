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
            //'codigo_salida' => 'required|unique:salidas',
            //'fecha' => 'required|date',
            //'total' => 'nullable|numeric',
            //'tipo' => 'nullable|in:venta,consumo',
            //'observaciones' => 'nullable|string',
            //'cantidad' => 'required|numeric',
            //'empleado_id' => 'required' //|exists:empleados,id'

            'lotes' => 'required|array|min:1',
            'lotes.*.id' => 'required|numeric|exists:lotes,id',
            'lotes.*.cantidad' => 'required|numeric|min:1',
        ]);

        DB::beginTransaction();
        try{

            // guardar
            $salida = new Salida();
            $salida->codigo_salida = Salida::generarCodigoSalida();
            $salida->fecha = date('Y-m-d H:i:s'); //now()
            $salida->total = 0; // VER ESTO, TOTAL FISICO AUN NO CALCULADO
            $salida->tipo = 2 ;// 1 para venta, 2 para consumo
            //$salida->observaciones = $request->observaciones;
            $salida->empleado_id = (int) $request->empleado_id;
            $salida->save();

            $lotes = $request->lotes; // array de lotes con id y cantidad
            //$calculatedTotal = 0.0;

            foreach($lotes as $lot){
                    $loteId = (int) $lot["id"];
                    $cantidadSalida = (int) $lot["cantidad"]; //cantidad que el frontend que pide (ver arriba el ejemplo del request)

                    $lote = Lote::where('id', $loteId)->lockForUpdate()->firstOrFail();
                    //$precioUnitario = (float) $lote->costo_unitario;

                    //verificar si el lote tiene stock suficiente
                    if ($lote->cantidad < $cantidadSalida) {
                        throw new \Exception("Stock insuficiente para el lote {$loteId}. Disponible: {$lote->cantidad}, Solicitado: {$cantidadSalida}");
                    }
                    $salida->lotes()->attach($loteId, [
                        'cantidad' => $cantidadSalida,// La cantidad que el frontend solicita para ese lote
                        //'observaciones' => $lot['observaciones'] ?? null
                        ]);
                    $lote->cantidad -= $cantidadSalida; // restamos la cantidad vendida del stock del lote
                    $lote->save(); // guardamos los cambios en el lote

                }
                $salida->save(); // guardamos los cambios en la salida
                DB::commit();

                return response()->json(['message' => 'Salida registrada exitosamente', 'data' => $salida]);

        }catch(\Exception $e){
            DB::rollBack();
            return response()->json(['message' => 'Error al registrar la salida', 'error' => $e->getMessage()], 500);
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
