<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuarioController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1/auth')->group(function(){

    // login,   register
    Route::post("/login", [AuthController::class, "login"]);
    Route::post("/register", [AuthController::class, "registro"]);

    // For this group we protect with a middleware (sanctum) through of tokens
    Route::middleware('auth:sanctum')->group(function(){
            // profile, logout
        Route::get("/perfil", [AuthController::class, "miPerfil"]);
        Route::post("/logout", [AuthController::class, "cerrar"]);
    });
});

// CRUD Api para Usuario (esto conectarara con su controllador: UsuarioController) 
Route::apiResource("/admin/usuario", UsuarioController::class); // ->middleware('auth:sanctum');
    