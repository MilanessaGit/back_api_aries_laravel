<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Listar
        // User.all();
        // User.paginate(5); mostrar 5 usuario con paginacion
        $usuarios = User::get();

        return response()->json($usuarios);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    //REGISTRO DE NUEVOS USUARIOS
    public function store(Request $request)
    {
        // Guardar lo enviado($request)
        
        //VALIDACION
        $request->validate([
            "name" => "required",
            "email" => "required|email|unique:users",
            "password" => "required"
        ]);
        
        // REGISTRAR (INSERTAR CON ORM) | GUARDAR 
        $usuario = new User;
        $usuario->name = $request->name;
        $usuario->email = $request->email;
        $usuario->password =  bcrypt($request->password);
        $usuario->save();

        return response()->json(["message" => "Usuario registrado correctamente", "data" => $usuario]);  
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // Mostrar por $id
        $usuario = User::find($id);
        // otra alternativa: User::where('id', $id)->first();
        return response()->json($usuario);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    // MODIFICAR O ACTUALIZAR 
    public function update(Request $request, $id)
    {
        // Modificar lo enviado($request), por $id
        //VALIDACION
        $request->validate([
            "name" => "required",
            "email" => "required|email|unique:users,email,$id", // excepto $id; caso particular
            "password" => "required"
        ]);
        $usuario = User::find($id);

        $usuario->name = $request->name;
        $usuario->email = $request->email;
        $usuario->password =  bcrypt($request->password);
        $usuario->update();

        return response()->json(["message" => "Usuario modificado correctamente", "data" => $usuario]);  
    
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Eliminar por $id 
        $usuario = User::find($id);
        $usuario->delete();

        return response()->json(["message" => "Usuario eliminado"]);
    }
}
