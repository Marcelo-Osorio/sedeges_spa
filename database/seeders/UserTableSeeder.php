<?php

namespace Database\Seeders;

use App\Models\Cargo;
use App\Models\Role;
use App\Models\Unidad;
use App\Models\UnidadMedida;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Cargo::create([
            "nombre" => "CARGO 1",
        ]);
        Unidad::create([
            "nombre" => "UNIDAD 1",
            "sigla" => "U1",
            "fecha_registro" => date("Y-m-d"),
        ]);
        Role::create([
            "nombre" => "SUPER USUARIO",
            "permisos" => 1,
            "usuarios" => 0,
        ]);
        Role::create([
            "nombre" => "ADMINISTRADOR",
            "permisos" => 0,
            "usuarios" => 1,
        ]);
        User::create([
            "usuario" => "admin",
            "nombre" => "admin",
            "paterno" => "admin",
            "materno" => "",
            "ci" => "0",
            "ci_exp" => "",
            "dir" => "",
            "email" => "",
            "fono" => "",
            "password" => "$2y$12$65d4fgZsvBV5Lc/AxNKh4eoUdbGyaczQ4sSco20feSQANshNLuxSC",
            "foto" => null,
            "tipo" => "",
            "cargo_id" => 1,
            "unidad_id" => 1,
            "role_id" => 1,
            "fecha_registro" => date("Y-m-d"),
            "acceso" => 1,
            "status" => 1,

        ]);
    }
}
