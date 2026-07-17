<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
//use Validator;

class AuthController extends Controller
{
    public function login(Request $request)// $request de data front
    {
        #validate si hay datos
        $credenciales = $request->validate([
            "email" => "required|email",
            "password" => "required"
        ]);

        //verificar correo
        if(!Auth::attempt($credenciales)){
            return response()->json(["message" => "No autorizado"],401);
        }
        // generar token
        $user = Auth::user();
        $tokenResult = $user->createToken("Token Auth");
        $token = $tokenResult->plainTextToken;
        // obtener rol
        $role = $user->roles()->first();

        // respuesta
        return response()->json([
            "access_token" => $token,
            "token_type" => "Bearer",
            "usuario" => $user,
            "role" => $role->nombre
        ]);
    }
    //
    public function registro(Request $request)
    {
        // Validar datos
        $request->validate([
            "name" => "required|string|max:255",
            "email" => "required|email|unique:users,email",
            "password" => "required|min:6",
            "c_password" => "required|same:password"
        ]);

        // Crear usuario
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        // $user->password = bcrypt($request->password);
        $user->save();

        // Buscar el rol vendedor
        $rolVendedor = Role::where('nombre', 'vendedor')->first();

        if (!$rolVendedor) {
            return response()->json([
                "message" => "El rol vendedor no existe."
            ], 500);
        }

        // Asignar rol
        $user->roles()->attach($rolVendedor->id);

        return response()->json([
            "message" => "Usuario registrado correctamente."
        ], 201);
    }
    //
    public function miPerfil(Request $request)
    {
        $user = Auth::user(); //otra alternativa $user = $request->user();
        //$user->ip = \Request::ip(); // Para obtener ip del cliente que se conecto
        //$user->ip = exec('getmac'); // Para obtener mac de la terminal(pc) que se conecta
        $role = $user->roles()->first();
        return response()->json([
            "user" => $user,
            "role" => $role
        ], 200);
    }
    //
    public function cerrar(Request $request) //request captura las peticiones del cliente
    {   //request es una inyeccion de independencia
        // otra alternativa: Auth::user()->tokens()->delete();
        $request->user()->tokens()->delete();
        return response()->json([ "message" => "Logout"], 200);
    }

}
