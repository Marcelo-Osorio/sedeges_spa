<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingresos', function (Blueprint $table) {
            $table->id();
            $table->string("codigo");
            $table->string("donacion", 60);
            $table->unsignedBigInteger("almacen_id");
            $table->unsignedBigInteger("unidad_id")->nullable();
            $table->string("proveedor")->nullable();
            $table->string("con_fondos")->nullable();
            $table->date("fecha_nota")->nullable();
            $table->string("nro_factura")->nullable();
            $table->date("fecha_factura")->nullable();
            $table->string("pedido_interno")->nullable();
            $table->decimal("total", 24, 2);
            $table->date("fecha_ingreso");
            $table->time("hora_ingreso")->nullable();
            $table->text("observaciones")->nullable();
            $table->string("para", 700)->nullable();
            $table->date("fecha_registro")->nullable();
            $table->unsignedBigInteger("user_id");
            $table->timestamps();

            $table->foreign("almacen_id")->on("almacens")->references("id");
            $table->foreign("unidad_id")->on("unidads")->references("id");
            $table->foreign("user_id")->on("users")->references("id");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingresos');
    }
};
