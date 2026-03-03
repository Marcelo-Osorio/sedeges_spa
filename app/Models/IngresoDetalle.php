<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngresoDetalle extends Model
{
    use HasFactory;

    protected $fillable = [
        "ingreso_id",
        "almacen_id",
        "unidad_id",
        "partida_id",
        "donacion",
        "producto_id",
        "unidad_medida_id",
        "cantidad",
        "costo",
        "total",
    ];

    public function ingreso()
    {
        return $this->belongsTo(Ingreso::class, 'ingreso_id');
    }

    public function egreso()
    {
        return $this->hasOne(Egreso::class, 'ingreso_detalle_id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function partida()
    {
        return $this->belongsTo(Partida::class, 'partida_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function unidad_medida()
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }
}
