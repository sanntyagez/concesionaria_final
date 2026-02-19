<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_id',
        'client_id',
        'total_amount',
        'down_payment',
        'payment_method',
        'installments_count',
        'interest_rate',
        'installment_value',
        'guarantor_info',
        'sale_date',
        'status',
    ];

    // Relación: La venta PERTENECE a un Auto
    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    // Relación: La venta PERTENECE a un Cliente
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // Relación: La venta TIENE MUCHOS Pagos (Cuotas)
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}