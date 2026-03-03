<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Egreso extends Model
{
    use HasFactory;

    protected $fillable = [
        "ingreso_id",
        "ingreso_detalle_id",
        "almacen_id",
        "partida_id",
        "producto_id",
        "destino_id",
        "cantidad",
        "costo",
        "total",
        "fecha_registro",
    ];

    protected $appends = ["fecha_registro_t", "s_cantidad", "s_total"];

    public function getSCantidadAttribute()
    {
        return (float)$this->ingreso_detalle->cantidad - (float)$this->cantidad;
    }

    public function getSTotalAttribute()
    {
        // Log::debug($this->ingreso_detalle->total);
        // Log::debug($this->total);
        return (float)$this->ingreso_detalle->total - (float)$this->total;
    }

    public function getFechaRegistroTAttribute()
    {
        return date("d/m/Y", strtotime($this->fecha_registro));
    }

    public function ingreso()
    {
        return $this->belongsTo(Ingreso::class, 'ingreso_id');
    }

    public function ingreso_detalle()
    {
        return $this->belongsTo(IngresoDetalle::class, 'ingreso_detalle_id');
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function destino()
    {
        return $this->belongsTo(Almacen::class, 'destino_id');
    }

    public function partida()
    {
        return $this->belongsTo(Partida::class, 'partida_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function ie_interno()
    {
        return $this->hasOne(IEInterno::class, 'egreso_id');
    }
}
