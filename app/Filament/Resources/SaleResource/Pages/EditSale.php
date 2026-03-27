<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Payment;
use Carbon\Carbon;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // Esta función se ejecuta JUSTO DESPUÉS de guardar los cambios en la Venta
    protected function afterSave(): void
    {
        $sale = $this->record;

        // 1. Recalculamos el nuevo valor de la cuota según los datos editados
        $saldo = $sale->total_amount - $sale->down_payment;

        if ($saldo > 0 && $sale->installments_count > 0) {
            $montoConInteres = $saldo * (1 + ($sale->interest_rate / 100));
            $valorCuota = $montoConInteres / $sale->installments_count;

            // 2. Contamos cuántas cuotas ya están PAGADAS 
            $pagadas = $sale->payments()->whereNotNull('paid_at')->count();

            // 3. Borramos SOLO las cuotas que están PENDIENTES
            $sale->payments()->whereNull('paid_at')->delete();

            // 4. Generamos las cuotas faltantes con el NUEVO valor y las nuevas fechas
            // Empezamos el bucle desde la cuota siguiente a la última pagada
            for ($i = $pagadas + 1; $i <= $sale->installments_count; $i++) {
                Payment::create([
                    'sale_id' => $sale->id,
                    'number' => $i,
                    'amount' => $valorCuota,
                    'due_date' => Carbon::parse($sale->sale_date)->addMonths($i),
                    'paid_at' => null, // Nacen impagas
                ]);
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        // Esto recarga la misma página de edición en la que estás
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}