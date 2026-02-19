<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand',
        'model',
        'plate',
        'year',
        'price',
        'color',
        'image_path',
        'status', // available, sold, reserved
    ];

    // Relación: Un auto puede tener ventas (historial)
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}