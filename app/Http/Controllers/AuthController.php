<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        // respuesta
        return response()->json([
            "access_token" => $token,
            "token_type" => "Bearer",
            "usuario" => $user
        ]);
    }
    //
    public function registro(Request $request)
    {
        // validate
        $request->validate([
            "name" => "required",
            "email" => "required|email|unique:users",
            "password" => "required",
            "c_password" => "required|same:password"
        ]);

        // register user (insert with orm)
        $u = new User;
        $u->name = $request->name;
        $u->email = $request->email;
        $u->password = bcrypt($request->password);
        $u->save();

        //response
        return response()->json([ "message" => "El usuario ha sido registrado"], 201);
    }
    //
    public function miPerfil()
    {
        $user = Auth::user(); //otra alternativa $user = $request->user();
        return response()->json($user, 200);
    }
    //
    public function cerrar(Request $request) //request captura las peticiones del cliente
    {   //request es una inyeccion de independencia
        // otra alternativa: Auth::user()->tokens()->delete();
        $request->user()->tokens()->delete();
        return response()->json([ "message" => "Logout"], 200);
    }

}
