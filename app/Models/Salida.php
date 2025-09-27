<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salida extends Model
{
    use HasFactory;

    public function empleado(){ //Una salida pertenece a un empleado
        return $this->belongsTo(Empleado::class);
    }
    
    // N:M
    public function lotes(){ //Una salida tiene muchos lote(s)
        return $this->belongsToMany(Lote::class); //->withPivot('cantidad'); //->withPivot('cantidad') es para acceder a la columna cantidad de la tabla intermedia
    }
}
