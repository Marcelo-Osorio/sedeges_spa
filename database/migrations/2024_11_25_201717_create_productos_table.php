<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogo_items', function (Blueprint $table) {
            $table->id();
            $table->text("nombre");
            $table->string("grupo", 60)->nullable();
            $table->string("abreviatura", 60)->nullable();
            $table->date("fecha_registro")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_items');
    }
};
