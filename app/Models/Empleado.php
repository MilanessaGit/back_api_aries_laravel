<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    use HasFactory;
    /*public function usuario(){ //Un empleado pertenece a un usuario
        return $this->belongsTo(User::class);
    }*/
    public function ventas(){ //Un empleado tiene muchas venta(s)
        return $this->hasMany(Venta::class);
    }
    public function entradas(){ //Un empleado tiene muchas entrada(s)
        return $this->hasMany(Entrada::class);
    }
    public function salidas(){ //Un empleado tiene muchas salida(s)
        return $this->hasMany(Salida::class);
    }
}
