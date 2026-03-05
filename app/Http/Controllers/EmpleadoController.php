<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use Illuminate\Http\Request;

class EmpleadoController extends Controller
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
            $empleados = Empleado::where('ci_nit' , 'like', "%" . $q . "%")->orderBy($orderby)->paginate($limit);
        }else{
            $empleados = Empleado::with('user:id,name,email')->orderBy($orderby)->paginate($limit);
        }
        return response()->json($empleados);
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
            'nombre' => 'required|string',
            'apellido' => 'required|string',
            'ci_nit' => 'required|string|unique:empleados',
            'edad' => 'required|integer',
            'telefono' => 'nullable|string',
            'direccion' => 'nullable|string'
            //,'user_id' => 'required'
        ]);

        //guardar
        $emp = new Empleado();
        $emp->nombre = $request->nombre;
        $emp->apellido = $request->apellido;
        $emp->ci_nit = $request->ci_nit;
        $emp->edad = $request->edad;
        $emp->telefono = $request->telefono;
        $emp->direccion = $request->direccion;
        //$emp->user_id = $request->user_id;
        $emp->save();

        return response()->json(['message' => 'Empleado creado exitosamente'], 201);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $empleado = Empleado::find($id);
        if (!$empleado) {
            return response()->json(['message' => 'Empleado no encontrado'], 404);
        }

        return response()->json($empleado, 200);
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
        //validar
        $request->validate([
            'nombre' => 'required|string',
            'apellido' => 'required|string',
            //'ci_nit' => 'required|string|unique:empleados,ci_nit,' . $id,
            'edad' => 'required|integer',
            'telefono' => 'nullable|string',
            'direccion' => 'nullable|string'
            //,'user_id' => 'required'
        ]);

        //guardar

        $emp = Empleado::find($id);
        $emp->nombre = $request->nombre;
        $emp->apellido = $request->apellido;
        $emp->ci_nit = $request->ci_nit;
        $emp->edad = $request->edad;
        $emp->telefono = $request->telefono;
        $emp->direccion = $request->direccion;
        //$emp->user_id = $request->user_id;
        $emp->update();

        return response()->json(['message' => 'Empleado Modificado Exitosamente'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $emp = Empleado::find($id);
        $emp->delete();

        return response()->json(['message' => 'Empleado Eliminado Exitosamente'], 200);
    }
}
