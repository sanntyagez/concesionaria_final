<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfController; 

// --- RUTAS DEL SISTEMA ---

Route::get('/', function () {
    return view('welcome');
});

// Ruta para descargar el PDF
Route::get('/payments/{payment}/pdf', [PdfController::class, 'downloadReceipt'])->name('payments.pdf');