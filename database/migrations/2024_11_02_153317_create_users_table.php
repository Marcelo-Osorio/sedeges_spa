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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('usuario')->unique();
            $table->string('nombre');
            $table->string('paterno');
            $table->string('materno')->nullable();
            $table->string('ci');
            $table->string('ci_exp');
            $table->string('dir', 255)->nullable();
            $table->string('email')->nullable();
            $table->string('fono', 255);
            $table->string('password');
            $table->string('foto', 255)->nullable();
            $table->string("tipo");
            $table->unsignedBigInteger("cargo_id")->nullable();
            $table->unsignedBigInteger("unidad_id")->nullable();
            $table->unsignedBigInteger("almacen_id")->nullable();
            $table->integer("almacen_todos")->default(0);
            $table->unsignedBigInteger("role_id")->nullable();
            $table->date("fecha_registro");
            $table->integer("acceso");
            $table->integer("status")->default(1);
            $table->timestamps();

            $table->foreign("cargo_id")->on("cargos")->references("id");
            $table->foreign("unidad_id")->on("unidads")->references("id");
            $table->foreign("role_id")->on("roles")->references("id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
