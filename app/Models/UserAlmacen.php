<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAlmacen extends Model
{
    use HasFactory;

    protected $fillable = [
        "user_id",
        "almacen_id",
    ];
}
