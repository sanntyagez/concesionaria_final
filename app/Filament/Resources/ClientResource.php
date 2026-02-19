<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-users'; // Ícono de personitas
    
    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $modelLabel = 'Cliente';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre Completo')
                    ->required()
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('dni')
                    ->label('DNI')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->numeric(),
                
                Forms\Components\TextInput::make('phone')
                    ->label('Teléfono / WhatsApp')
                    ->tel()
                    ->required(), // Obligatorio para poder cobrarle
                
                Forms\Components\TextInput::make('address')
                    ->label('Dirección')
                    ->maxLength(255),
                
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('dni')
                    ->label('DNI')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->icon('heroicon-m-phone'),
                
                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->limit(30), // Corta el texto si es muy largo
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}