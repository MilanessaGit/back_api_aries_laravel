<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;
    public function cliente(){ //Una venta pertenece a un cliente
        return $this->belongsTo(Cliente::class);
    }
    public function empleado(){ //Una venta pertenece a un empleado
        return $this->belongsTo(Empleado::class);
    }
    
    // N:M
    public function lotes(){ //Una venta tiene muchos lote(s)
        return $this->belongsToMany(Lote::class);// ->withPivot('cantidad','precio_venta'); //->withPivot('cantidad','precio_venta') es para acceder a las columnas cantidad y precio_venta de la tabla intermedia
    }

    public static function generarCodigoVenta()
    {
        $ultimaVenta = self::latest('id')->first();
        $codigo = 'VEN-' . str_pad($ultimaVenta ? $ultimaVenta->id + 1 : 1, 4, '0', STR_PAD_LEFT);
        return $codigo;
    }
}
