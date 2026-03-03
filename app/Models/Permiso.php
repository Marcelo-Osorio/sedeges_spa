<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    use HasFactory;

    protected $fillable = [
        "role_id",
        "modulo_id"
    ];

    public function modulo()
    {
        return $this->belongsTo(Modulo::class, 'modulo_id');
    }
}
