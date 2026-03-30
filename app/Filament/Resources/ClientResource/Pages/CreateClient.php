<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    // Redirección al listado tras crear
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}