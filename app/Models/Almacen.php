<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    use HasFactory;
    // N:M
    public function lotes(){ //Un almacen tiene muchos lote(s)
        return $this->belongsToMany(Lote::class); //->withPivot('cantidad'); //->withPivot('cantidad') es para acceder a la columna cantidad de la tabla intermedia
    }
}
