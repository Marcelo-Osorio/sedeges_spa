<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConfiguracionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Configuracion::create([
            "razon_social" => "UNIBIENES S.A.",
            "alias" => "UB",
            "logo" => "logo.png"
        ]);
    }
}
