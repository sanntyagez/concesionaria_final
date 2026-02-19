<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre completo
            $table->string('dni')->unique(); // DNI
            $table->string('phone')->nullable(); // Telefono (Vital para cobrar)
            $table->string('address')->nullable(); // Direccion
            $table->string('email')->nullable(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};