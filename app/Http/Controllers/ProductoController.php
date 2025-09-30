<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
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
            $productos = Producto::where('nombre', 'like', '%' . $q . '%')
                ->orderBy($orderby, 'asc')
                ->paginate($limit);
        } else {
            $productos = Producto::orderBy($orderby, 'desc')->paginate($limit);
        }

        return response()->json($productos, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        // validar
        $request->validate([
            "nombre" => 'required|min:3|max:200',
            "categoria_id" => "required"
        ]);
        // subir img
        $direccion_imagen = "";


        // guardar
        $prod = new Producto();
        $prod->codigo = $request->codigo_producto;
        $prod->nombre = $request->nombre;
        $prod->precio = $request->precio_sugerido;
        //  - $prod->stock = $request->stock;
        $prod->modelo = $request->modelo;
        $prod->color = $request->color;
        $prod->gama = $request->gama;
        $prod->peso = $request->peso;
        $prod->dimensiones = $request->dimensiones;
        $prod->descripcion = $request->descripcion;
        $prod->caracteristicas = $request->caracteristicas_tecnicas;
        $prod->categoria_id = $request->categoria_id;
        $prod->imagen = $direccion_imagen;
        $prod->save();

        // responder
        return response()->json(["mensaje" => "Producto registrado", "data" => $prod]);
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
