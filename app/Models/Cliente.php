<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;
    public function ventas(){ //Un cliente tiene muchas venta(s)
        return $this->hasMany(Venta::class);
    }
}
