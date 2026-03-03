<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IEInterno extends Model
{
    use HasFactory;

    protected $fillable = [
        "almacen_id",
        "producto_id",
        "ingreso_id",
        "ingreso_detalle_id",
        "egreso_id",
        "icantidad",
        "icosto",
        "itotal",
        "ecantidad",
        "etotal",
        "registro_egreso",
        "fecha_registro",
        "fecha_egreso"
    ];

    protected $appends = ["fecha_registro_t", "fecha_egreso_t", "s_cantidad", "s_total"];


    public function getSCantidadAttribute()
    {
        return (float)$this->icantidad - ($this->ecantidad ? (float)$this->ecantidad : 0);
    }

    public function getSTotalAttribute()
    {
        return (float)$this->itotal - ($this->etotal ? (float)$this->etotal : 0);
    }

    public function getFechaEgresoTAttribute()
    {
        if ($this->fecha_egreso) {
            return date("d/m/Y", strtotime($this->fecha_egreso));
        }
        return "-";
    }

    public function getFechaRegistroTAttribute()
    {
        return date("d/m/Y", strtotime($this->fecha_registro));
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function ingreso()
    {
        return $this->belongsTo(Ingreso::class, 'ingreso_id');
    }

    public function ingreso_detalle()
    {
        return $this->belongsTo(IngresoDetalle::class, 'ingreso_detalle_id');
    }

    public function egreso()
    {
        return $this->belongsTo(Egreso::class, 'egreso_id');
    }
}
