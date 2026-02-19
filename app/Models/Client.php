<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'dni',
        'phone',
        'address',
        'email',
    ];

    // Relación: Un cliente puede tener muchas compras
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}