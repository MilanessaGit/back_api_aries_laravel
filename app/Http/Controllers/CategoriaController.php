<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $categorias = Categoria::get();

        return response()->json($categorias, 200);
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
            "nombre" => "required|unique:categorias"
            // "descripcion" =>
        ]);
            
        //guardar
        $cat = new Categoria();
        $cat->nombre = $request->nombre;
        $cat->descripcion = $request->descripcion;
        $cat->save();

        return response()->json(["mensaje" => "Categoria Registrada"], 201);
            
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $categoria = Categoria::find($id);

        return response()->json($categoria, 200);
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
        //Validar
        $request->validate([
            "nombre" => "required|unique:categorias,nombre,".$id
            // "descripcion" =>
        ]);

        //Guardar
        $cat = Categoria::find($id);
        $cat->nombre = $request->nombre;
        //$cat->descripcion = $request->descripcion;
        $cat->update();
        return response()->json(["mensaje" => "Categoria Modificada"], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $cat = Categoria::find($id);
        $cat->delete();
        return response()->json(["mensaje" => "Categoria Eliminada"], 200);
    }
}
