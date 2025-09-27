<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entrada extends Model
{
    use HasFactory;
    public function proveedor(){ //Una entrada pertenece a un proveedor
        return $this->belongsTo(Proveedor::class);
    }
    public function empleado(){ //Una entrada pertenece a un empleado
        return $this->belongsTo(Empleado::class);
    }

    // N:M
    public function lotes(){ //Una entrada tiene muchos lote(s)
        return $this->belongsToMany(Lote::class);// ->withPivot('cantidad','
    }    
}
