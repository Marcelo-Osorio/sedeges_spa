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
        Schema::create('ingresos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("almacen_id");
            $table->unsignedBigInteger("unidad_id")->nullable();
            $table->string("codigo")->unique()->nullable();
            $table->integer("nro")->nullable();
            $table->string("donacion");
            $table->date("fecha_ingreso");
            $table->date("fecha_registro")->nullable();
            $table->unsignedBigInteger("user_id");
            $table->timestamps();

            $table->foreign("almacen_id")->on("almacens")->references("id");
            $table->foreign("unidad_id")->on("unidads")->references("id");
            $table->foreign("user_id")->on("users")->references("id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingresos');
    }
};
