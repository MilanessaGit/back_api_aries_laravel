<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use Illuminate\Http\Request;

class LoteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) // Inyeccion de dependencias
    {
        //  /api/producto?page=2&limit=10&q=laptop&orderby=id
        // $page = $request->page;
        $limit = $request->limit ? $request->limit : 10;
        $q = $request->q;
        $orderby = $request->orderby ? $request->orderby : 'id';

        if ($q) {
            $lotes = Lote::where('codigo_lote', 'like', '%' . $q . '%')
                ->orderBy($orderby, 'asc')
                ->paginate($limit);
        } else {
            $lotes = Lote::with('producto:id,nombre,codigo_producto')->orderBy($orderby, 'desc')->paginate($limit);
        }

        return response()->json($lotes, 200);
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
            "codigo_lote" => 'required|min:3|max:200',
            "producto_id" => "required"
        ]);
    


        // guardar
        $lot = new Lote();

        $lot->codigo_lote = $request->codigo_lote;
        $lot->costo_unitario = $request->costo_unitario;
        $lot->cantidad = $request->cantidad; 
        
        //$lot->estado = $request->estado;
        //$lot->fecha_ingreso = $request->fecha_ingreso;
        //$lot->fecha_caducidad = $request->fecha_caducidad;
        //$lot->trazabilidad = $request->trazabilidad;

        $lot->producto_id = $request->producto_id;
        $lot->save();

        // responder
        return response()->json(["mensaje" => "Lote registrado", "data" => $lot]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $lote = Lote::find($id);

        return response()->json($lote, 200);
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
        // validar
        $request->validate([
            "codigo_lote" => 'required|min:3|max:200',
            "producto_id" => "required"
        ]);

        // gualot
        $lot = Lote::find($id);
        $lot->codigo_lote = $request->codigo_lote;
        
        $lot->costo_unitario = $request->costo_unitario;
        $lot->cantidad = $request->cantidad;
        $lot->fecha_ingreso = $request->fecha_ingreso;
        $lot->fecha_caducidad = $request->fecha_caducidad;
        $lot->estado = $request->estado;
        $lot->trazabilidad = $request->trazabilidad;

        
        $lot->producto_id = $request->producto_id;
       
        $lot->update();

        // responder
        return response()->json(["mensaje" => "Lote Actualizado", "data" => $lot]);
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
        $prod = Lote::find($id);
        $prod->delete();
        return response()->json(["mensaje" => "Producto Eliminado"]);
    }
}
