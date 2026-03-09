<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->q){
            $prov = Proveedor::where('ci_nit' , 'like', "%" . $request->q . "%")->first();
        }else{
            $prov = Proveedor::paginate(5);
        }
        return response()->json($prov);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $proveedor = new Proveedor();
        $proveedor->codigo_proveedor = $request->codigo_proveedor;
        $proveedor->nombre = $request->nombre;
        $proveedor->apellido = $request->apellido;
        $proveedor->ci_nit = $request->ci_nit;
        $proveedor->telefono = $request->telefono;
        $proveedor->direccion = $request->direccion;
        $proveedor->save();
        return response()->json($proveedor);
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
