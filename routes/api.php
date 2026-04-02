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
    Route::post("/login", [AuthController::class, "login"]); //->middleware('throttle:10,1'); // Limitar a 5 intentos por minuto para evitar ataques de fuerza bruta
    Route::post("/register", [AuthController::class, "registro"]); //mover al admin, solo el admin puede crear usuarios, o crear un endpoint para que el admin cree usuarios, y este endpoint si lo protejo con el middleware de auth:sanctum y role:admin

    // For this group we protect with a middleware (sanctum) through of tokens
    Route::middleware('auth:sanctum')->group(function(){
            // profile, logout
        Route::get("/perfil", [AuthController::class, "miPerfil"]);
        Route::post("/logout", [AuthController::class, "cerrar"]);

    });
    
});

Route::get('/recomendar/{producto_id}', [RecomendacionController::class, 'recomendar']);
Route::get('/prediccion', [ProductoController::class, 'prediccion']);


Route::prefix('admin')->middleware('auth:sanctum', 'role:admin')->group(function(){
    
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
    Route::apiResource("role", RoleController::class); // ->middleware('auth:sanctum');
});

Route::prefix('supervisor')->middleware('auth:sanctum', 'role:supervisor' )->group(function(){
    Route::apiResource('categoria', CategoriaController::class)->only(['index', 'show', 'store']);
    Route::apiResource('producto', ProductoController::class)->only(['index', 'show', 'store']);
    Route::apiResource('lote', LoteController::class)->only(['index', 'show', 'store']);

    Route::apiResource('entrada', EntradaController::class)->only(['index', 'show', 'store']);
    Route::apiResource('salida', SalidaController::class)->only(['index', 'show', 'store']);

    Route::apiResource('cliente', ClienteController::class)->only(['index', 'show', 'store']);
    Route::apiResource('proveedor', ProveedorController::class)->only(['index', 'show', 'store']);
    
});

Route::prefix('vendedor')->middleware('auth:sanctum', 'role:vendedor')->group(function(){
    Route::apiResource('categoria', CategoriaController::class)->only(['index', 'show']);
    Route::apiResource('lote', LoteController::class)->only(['index', 'show']);
    Route::apiResource('producto', ProductoController::class)->only(['index', 'show']);

    
    Route::apiResource('cliente', ClienteController::class)->only(['index', 'show']);
    Route::apiResource('venta', VentaController::class)->only(['index', 'show', 'store']);
});