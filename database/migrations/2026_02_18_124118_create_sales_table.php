<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained(); // Qué auto
            $table->foreignId('client_id')->constrained(); // Qué cliente
            
            // --- MONTOS ---
            $table->decimal('total_amount', 15, 2); // En cuánto se vendió el auto
            $table->decimal('down_payment', 15, 2)->default(0); // Entrega inicial (Plata en mano)
            $table->string('payment_method')->default('efectivo'); // ¿Cómo pagó la entrega? (Efectivo/Transferencia)
            
            // --- FINANCIACIÓN (Opción A: Interés sobre saldo) ---
            $table->integer('installments_count')->default(1); // Cantidad cuotas (12, 18, 36...)
            $table->decimal('interest_rate', 5, 2)->default(0); // Porcentaje Interés (10%, 20%)
            $table->decimal('installment_value', 15, 2)->default(0); // Valor FIJO de la cuota (calculado al crear la venta)
            
            // --- EXTRAS ---
            $table->text('guarantor_info')->nullable(); // Info del Garante (Nombre, DNI, Tel) - Opcional
            $table->date('sale_date'); // Fecha de la operación
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active'); // Estado de la venta
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};