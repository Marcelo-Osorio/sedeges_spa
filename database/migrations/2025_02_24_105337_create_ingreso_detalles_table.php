<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ingreso_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("ingreso_id");
            $table->unsignedBigInteger("almacen_id");
            $table->unsignedBigInteger("unidad_id")->nullable();
            $table->unsignedBigInteger("partida_id")->nullable();
            $table->string("donacion")->default("NO");
            $table->unsignedBigInteger("producto_id");
            $table->unsignedBigInteger("unidad_medida_id");
            $table->integer("cantidad");
            $table->decimal("costo", 24, 2);
            $table->decimal("total", 24, 2);
            $table->timestamps();

            $table->foreign("ingreso_id")->on("ingresos")->references("id");
            $table->foreign("almacen_id")->on("almacens")->references("id");
            $table->foreign("unidad_id")->on("unidads")->references("id");
            $table->foreign("partida_id")->on("partidas")->references("id");
            $table->foreign("producto_id")->on("productos")->references("id");
            $table->foreign("unidad_medida_id")->on("unidad_medidas")->references("id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingreso_detalles');
    }
};
