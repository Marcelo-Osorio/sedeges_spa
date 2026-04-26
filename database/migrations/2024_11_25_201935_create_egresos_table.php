<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('egresos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("ingreso_id")->nullable();
            $table->unsignedBigInteger("ingreso_detalle_id")->unique();
            $table->unsignedBigInteger("almacen_id");
            $table->unsignedBigInteger("partida_id")->nullable();
            $table->unsignedBigInteger("item_id");
            $table->unsignedBigInteger("destino_id")->nullable();
            $table->integer("cantidad")->nullable();
            $table->decimal("costo", 24, 2)->nullable();
            $table->decimal("total", 24, 2);
            $table->date("fecha_registro")->nullable();
            $table->integer("editable")->default(1);
            $table->timestamps();

            $table->foreign("ingreso_id")->on("ingresos")->references("id");
            $table->foreign("ingreso_detalle_id")->on("ingreso_detalles")->references("id");
            $table->foreign("almacen_id")->on("almacens")->references("id");
            $table->foreign("partida_id")->on("partidas")->references("id");
            $table->foreign("item_id")->on("catalogo_items")->references("id");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('egresos');
    }
};
