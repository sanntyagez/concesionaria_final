<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete(); // Pertenece a una venta
            
            $table->integer('number'); // Cuota N° (1, 2, 3...)
            $table->decimal('amount', 15, 2); // Monto que DEBE pagar
            $table->date('due_date'); // Fecha de Vencimiento (El día 10 de cada mes)
            
            // --- DATOS DEL PAGO REAL ---
            $table->date('paid_at')->nullable(); // ¿Cuándo pagó? (Si es NULL, es que debe)
            $table->string('payment_method')->nullable(); // Efectivo, Transferencia, Cheque
            $table->string('receipt_number')->nullable(); // N° de Recibo (para el PDF)
            $table->text('notes')->nullable(); // Notas (ej: "Pagó parcial", "Vino el hijo")
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};