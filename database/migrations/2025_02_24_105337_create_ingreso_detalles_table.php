<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingreso_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("ingreso_id");
            $table->unsignedBigInteger("almacen_id");
            $table->unsignedBigInteger("unidad_id")->nullable();
            $table->unsignedBigInteger("partida_id")->nullable();
            $table->string("donacion", 60)->default("NO");
            $table->unsignedBigInteger("item_id");
            $table->unsignedBigInteger("unidad_medida_id")->nullable();
            $table->integer("cantidad")->nullable();
            $table->decimal("costo", 24, 2)->nullable();
            $table->decimal("total", 24, 2);
            $table->timestamps();

            $table->foreign("ingreso_id")->on("ingresos")->references("id");
            $table->foreign("almacen_id")->on("almacens")->references("id");
            $table->foreign("unidad_id")->on("unidads")->references("id");
            $table->foreign("partida_id")->on("partidas")->references("id");
            $table->foreign("item_id")->on("catalogo_items")->references("id");
            $table->foreign("unidad_medida_id")->on("unidad_medidas")->references("id");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingreso_detalles');
    }
};
