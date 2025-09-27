<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    public function categoria(){ //Un producto pertenece a una categoria
        return $this->belongsTo(Categoria::class);
    }
    public function lotes(){ //Un producto tiene muchos lote(s)
        return $this->hasMany(Lote::class);
    } 
}
