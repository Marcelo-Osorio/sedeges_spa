<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        "nombre",
        "permisos",
        "usuarios"
    ];

    public function o_permisos()
    {
        return $this->hasMany(Permiso::class, 'role_id');
    }
}
