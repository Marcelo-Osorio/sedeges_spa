<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    use HasFactory;

    protected $fillable = [
        "nombre",
        "grupo", //CENTRAL,FARMACIAS,PROGRAMAS,CENTROS
        "descripcion",
        "fecha_registro",
    ];

    protected $appends = ["fecha_registro_t"];

    public function getFechaRegistroTAttribute()
    {
        if ($this->fecha_registro)
            return date("d/m/Y", strtotime($this->fecha_registro));
        return "-";
    }
}
