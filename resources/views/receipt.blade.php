<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo de Pago - {{ config('app.name') }}</title>
    <style>
        body { font-family: sans-serif; padding: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .company-name { font-size: 26px; font-weight: bold; text-transform: uppercase; margin-top: 10px; }
        .receipt-info { text-align: right; margin-bottom: 20px; font-size: 14px; }
        .content { margin-top: 20px; line-height: 1.8; font-size: 16px; }
        .amount-box { 
            border: 2px solid #000; 
            padding: 10px 20px; 
            font-size: 22px; 
            font-weight: bold; 
            display: inline-block; 
            margin-top: 10px;
            background-color: #f9f9f9;
        }
        .signature { margin-top: 80px; text-align: right; }
        .line { border-top: 1px solid #000; width: 220px; display: inline-block; }
        .footer { margin-top: 50px; font-size: 12px; text-align: center; color: #777; border-top: 1px dashed #ccc; padding-top: 10px; }
        .logo { max-height: 80px; width: auto; margin-bottom: 5px; }
    </style>
</head>
<body>

    <div class="header">
        @if(env('COMPANY_LOGO'))
            <img src="{{ public_path(env('COMPANY_LOGO')) }}" alt="Logo de la Empresa" class="logo">
        @endif

        <div class="company-name">{{ config('app.name') }}</div>
        <div>Venta de Automotores Seleccionados</div>
    </div>

    <div class="receipt-info">
        <strong>Recibo N°:</strong> {{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}<br>
        <strong>Fecha de Pago:</strong> {{ $payment->paid_at ? $payment->paid_at->format('d/m/Y') : date('d/m/Y') }}
    </div>

    <div class="content">
        <p>
            Recibí de el/la Sr/a <strong>{{ $payment->sale->client->name }}</strong> (DNI: {{ $payment->sale->client->dni }})
            <br>
            la suma de pesos:
        </p>

        <div class="amount-box">
            $ {{ number_format($payment->amount, 2, ',', '.') }}
        </div>

        <p>
            En concepto de <strong>Cuota N° {{ $payment->number }} de {{ $payment->sale->installments_count }}</strong>
            correspondiente a la compra del vehículo: <br>
            <strong>{{ $payment->sale->car->brand }} {{ $payment->sale->car->model }}</strong> - Patente: {{ $payment->sale->car->plate }}
        </p>

        <p>
            <em>Forma de Pago: {{ ucfirst($payment->payment_method ?? 'Efectivo') }}</em>
        </p>
    </div>

    <div class="signature">
        <div class="line"></div>
        <br>Firma y Aclaración autorizada
    </div>

    <div class="footer">
        Este documento es un comprobante válido de pago emitido por el sistema de gestión de <strong>{{ config('app.name') }}</strong>. <br>
        Gracias por su confianza.
    </div>

</body>
</html>