<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    use HasFactory;

    public function producto(){ //Un lote pertenece a un producto
        return $this->belongsTo(Producto::class);
    }

    // N:M
    public function almacenes(){ //Un lote pertenece a muchos almacenes
        return $this->belongsToMany(Almacen::class);  //->withPivot('cantidad'); //->withPivot('cantidad') es para acceder a la columna cantidad de la tabla intermedia
    }
    public function ventas(){ //Un lote pertenece a muchas ventas
        return $this->belongsToMany(Venta::class)->withPivot(["cantidad", "precio_unitario"])->withTimestamps(); //->withPivot('cantidad','precio_venta'); //->withPivot('cantidad','precio_venta') es para acceder a las columnas cantidad y precio_venta de la tabla intermedia
    }
    public function salidas(){ //Un lote pertenece a muchas salidas
        return $this->belongsToMany(Salida::class); //->withPivot('cantidad'); //->withPivot('cantidad') es para acceder a la columna cantidad de la tabla intermedia
    }
    public function entradas(){ //Un lote pertenece a muchas entradas
        return $this->belongsToMany(Entrada::class);// ->withPivot('cantidad','precio_compra'); //->withPivot('cantidad','precio_compra') es para acceder a las columnas cantidad y precio_compra de la tabla intermedia
    }
}
