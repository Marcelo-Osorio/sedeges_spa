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
        Schema::create('i_e_internos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("almacen_id");
            $table->unsignedBigInteger("producto_id");
            $table->unsignedBigInteger("ingreso_id");
            $table->unsignedBigInteger("egreso_id");
            $table->integer("icantidad");
            $table->decimal("icosto", 24, 2);
            $table->decimal("itotal", 24, 2);
            $table->integer("ecantidad")->nullable();
            $table->decimal("etotal", 24, 2)->nullable();
            $table->integer("registro_egreso")->default(0);
            $table->date("fecha_registro");
            $table->date("fecha_egreso")->nullable();
            $table->timestamps();

            $table->foreign("almacen_id")->on("almacens")->references("id");
            $table->foreign("producto_id")->on("productos")->references("id");
            $table->foreign("ingreso_id")->on("ingresos")->references("id");
            $table->foreign("egreso_id")->on("egresos")->references("id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('i_e_internos');
    }
};
