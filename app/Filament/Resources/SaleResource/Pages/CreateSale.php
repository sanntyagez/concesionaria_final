<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Payment; // Importamos el modelo de pagos
use App\Models\Car;     // Importamos el modelo de autos
use Filament\Resources\Pages\CreateRecord;
use Carbon\Carbon;      // Para manejar fechas

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    // Esta función se ejecuta AUTOMÁTICAMENTE después de guardar la venta
    protected function afterCreate(): void
    {
        // 1. Obtenemos la venta que acabamos de crear
        $sale = $this->record;

        // 2. Actualizamos el estado del auto a "Vendido"
        $car = Car::find($sale->car_id);
        if ($car) {
            $car->update(['status' => 'sold']);
        }

        // 3. Generamos las Cuotas (El Bucle Mágico)
        if ($sale->installments_count > 0) {
            
            // Calculamos monto de cuota de nuevo por seguridad
            $saldo = $sale->total_amount - $sale->down_payment;
            
            if ($saldo > 0) {
                $montoConInteres = $saldo * (1 + ($sale->interest_rate / 100));
                $valorCuota = $montoConInteres / $sale->installments_count;

                // Bucle: Repetir X veces
                for ($i = 1; $i <= $sale->installments_count; $i++) {
                    Payment::create([
                        'sale_id' => $sale->id,
                        'number' => $i,
                        'amount' => $valorCuota,
                        // Vencimiento: Hoy + i meses (Ej: Hoy es Feb, cuota 1 vence en Mar)
                        'due_date' => Carbon::parse($sale->sale_date)->addMonths($i),
                        'paid_at' => null, // Nace impaga
                    ]);
                }
            }
        }
    }
}