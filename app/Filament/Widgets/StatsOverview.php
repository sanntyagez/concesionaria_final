<?php

namespace App\Filament\Widgets;

use App\Models\Payment; 
use App\Models\Client;  
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
  protected function getStats(): array
{
    return [
        // Suma de 'amount' donde 'paid_at' está vacío (Saldo en la calle)
        Stat::make('Saldo en la Calle', '$' . number_format(\App\Models\Payment::whereNull('paid_at')->sum('amount'), 2, ',', '.'))
            ->description('Total pendiente de cobro')
            ->descriptionIcon('heroicon-m-banknotes')
            ->color('warning'),

        // Conteo de pagos donde 'paid_at' es NULL y la 'due_date' ya pasó
        Stat::make('Pagos Atrasados', \App\Models\Payment::whereNull('paid_at')
            ->where('due_date', '<', now())
            ->count())
            ->description('Cuotas vencidas hoy')
            ->descriptionIcon('heroicon-m-exclamation-triangle')
            ->color('danger'),
            
        // Opcional: Total cobrado en el mes
        Stat::make('Cobrado este Mes', '$' . number_format(\App\Models\Payment::whereNotNull('paid_at')
            ->whereMonth('paid_at', now()->month)
            ->sum('amount'), 2, ',', '.'))
            ->description('Dinero real ingresado')
            ->descriptionIcon('heroicon-m-check-badge')
            ->color('success'),
    ];
}
}
