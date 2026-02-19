<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf; // Importamos la librería
use Illuminate\Http\Request;

class PdfController extends Controller
{
    public function downloadReceipt(Payment $payment)
    {
        // 1. Cargamos el pago con los datos de la venta, el cliente y el auto
        $payment->load(['sale.client', 'sale.car']);

        // 2. Generamos el PDF usando la vista que creamos
        $pdf = Pdf::loadView('receipt', ['payment' => $payment]);

        // 3. Descargamos el archivo (Stream para verlo en el navegador, Download para bajarlo directo)
        return $pdf->stream('recibo-'.$payment->id.'.pdf');
    }
}