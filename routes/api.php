<?php

use App\Http\Controllers\AlmacenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\EntradaController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\RecomendacionController;
use App\Http\Controllers\SalidaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VentaController;

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

Route::get('/recomendar/{producto_id}', [RecomendacionController::class, 'recomendar']);

Route::prefix('admin')->middleware('auth:sanctum')->group(function(){

    

    Route::post('producto/{id}/imagen', [ProductoController::class, "actualizarImagen"]);

    // CRUD Api para Usuario (esto conectarara con su controllador: UsuarioController) 
    Route::apiResource("usuario", UsuarioController::class); // ->middleware('auth:sanctum');
    
    Route::apiResource("categoria", CategoriaController::class); // ->middleware('auth:sanctum');
    Route::apiResource("producto", ProductoController::class); // ->middleware('auth:sanctum');
    Route::apiResource("lote", LoteController::class); // ->middleware('auth:sanctum');
    Route::apiResource("almacen", AlmacenController::class); // ->middleware('auth:sanctum');
    Route::apiResource("cliente", ClienteController::class); // ->middleware('auth:sanctum');
    Route::apiResource("empleado", EmpleadoController::class); // ->middleware('auth:sanctum');
    Route::apiResource("proveedor", ProveedorController::class); // ->middleware('auth:sanctum');
    Route::apiResource("entrada", EntradaController::class); // ->middleware('auth:sanctum');
    Route::apiResource("salida", SalidaController::class); // ->middleware('auth:sanctum');
    Route::apiResource("venta", VentaController::class); // ->middleware('auth:sanctum');

});