<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;
    public function entrada(){ //Un pago pertenece a una entrada
        return $this->belongsTo(Entrada::class);
    }
}
