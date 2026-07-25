<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $fillable = [
        'entrada_id',
        'fecha_pago',
        'monto',
        'metodo_pago',
        'observaciones'
    ];

    // Un pago pertenece a una entrada
    public function entrada(){ //Un pago pertenece a una entrada
        return $this->belongsTo(Entrada::class);
    }
}
