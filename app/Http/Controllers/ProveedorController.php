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
        $limit = $request->limit ? $request->limit : 10;
        $q = $request->q;
        $orderby = $request->orderby ? $request->orderby : 'id';

        if($q){
            $proveedores = Proveedor::where('ci_nit' , 'like', "%" . $q . "%")->orderBy($orderby)->paginate($limit);
        }else{
            $proveedores = Proveedor::orderBy($orderby)->paginate($limit);
        }
        return response()->json($proveedores);
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
            'ci_nit' => 'required|string|unique:proveedors',
            'nombre' => 'required|string',
            'apellido' => 'required|string',
            'telefono' => 'nullable|string',
            'direccion' => 'nullable|string'
            
        ]);
        
        $proveedor = new Proveedor();
        $proveedor->codigo_proveedor = $request->codigo_proveedor;
        $proveedor->ci_nit = $request->ci_nit;
        $proveedor->nombre = $request->nombre;
        $proveedor->apellido = $request->apellido;
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
        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            return response()->json(['message' => 'Proveedor no encontrado'], 404);
        }

        return response()->json($proveedor, 200);
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
        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            return response()->json(['message' => 'Proveedor no encontrado'], 404);
        }

        $proveedor->codigo_proveedor = $request->codigo_proveedor;
        $proveedor->nombre = $request->nombre;
        $proveedor->apellido = $request->apellido;
        $proveedor->ci_nit = $request->ci_nit;
        $proveedor->telefono = $request->telefono;
        $proveedor->direccion = $request->direccion;
        $proveedor->save();

        return response()->json($proveedor, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            return response()->json(['message' => 'Proveedor no encontrado'], 404);
        }

        $proveedor->delete();
        return response()->json(['message' => 'Proveedor eliminado'], 200);
    }
}
