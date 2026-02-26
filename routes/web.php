<?php

use App\Http\Controllers\PdfController;
use Illuminate\Support\Facades\Route;

// Por ahora, dejamos que la raíz sea la página de bienvenida de Laravel
Route::get('/', function () {
    return view('welcome');
});

// Mantenemos esta ruta por si la usás para tus recibos
Route::get('/payments/{payment}/pdf', [PdfController::class, 'downloadReceipt'])->name('payments.pdf');