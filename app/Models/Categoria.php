<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;
    
    public function productos(){ //Una categoria tiene muchos producto(s)
        return $this->hasMany(Producto::class);
    }
}
