<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'number',
        'amount',
        'due_date',
        'paid_at',
        'payment_method',
        'receipt_number',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date', // Para que Laravel lo trate como fecha
        'paid_at' => 'date',
    ];

    // Relación: El pago PERTENECE a una Venta
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}