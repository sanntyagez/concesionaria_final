<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('brand'); // Marca (Fiat)
            $table->string('model'); // Modelo (Cronos)
            $table->string('plate')->unique(); // Patente
            $table->integer('year'); // Año
            $table->decimal('price', 15, 2); // Precio de Lista
            $table->string('color')->nullable();
            $table->string('image_path')->nullable(); // Foto del auto
            // Estados: Disponible, Vendido, Reservado
            $table->enum('status', ['available', 'sold', 'reserved'])->default('available'); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
