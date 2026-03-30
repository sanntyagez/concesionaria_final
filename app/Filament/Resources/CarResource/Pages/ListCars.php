<?php

namespace App\Filament\Resources\CarResource\Pages;

use App\Filament\Resources\CarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCars extends ListRecords
{
    protected static string $resource = CarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos')
                ->icon('heroicon-m-list-bullet'),
                
            'disponibles' => Tab::make('Disponibles')
                ->icon('heroicon-m-check-badge')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'available')),
                
            'vendidos' => Tab::make('Vendidos')
                ->icon('heroicon-m-currency-dollar')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'sold')),
                
            'apartados' => Tab::make('Reservados')
                ->icon('heroicon-m-clock')
                
                       ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'reserved')), 
        ];
    }
}