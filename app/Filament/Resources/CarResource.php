<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CarResource\Pages;
use App\Models\Car;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CarResource extends Resource
{
    protected static ?string $model = Car::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck'; // Ícono del camión/auto
    
    protected static ?string $navigationLabel = 'Autos'; // Nombre en el menú
    
    protected static ?string $modelLabel = 'Auto';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Vehículo')
                    ->schema([
                        Forms\Components\TextInput::make('brand')
                            ->label('Marca')
                            ->required()
                            ->placeholder('Ej: Fiat'),
                        
                        Forms\Components\TextInput::make('model')
                            ->label('Modelo')
                            ->required()
                            ->placeholder('Ej: Cronos'),
                        
                        Forms\Components\TextInput::make('plate')
                            ->label('Patente')
                            ->required()
                            ->unique(ignoreRecord: true) // Que no se repita
                            ->placeholder('AA 123 BB'),
                        
                        Forms\Components\TextInput::make('year')
                            ->label('Año')
                            ->numeric()
                            ->maxValue(date('Y') + 1), // No permite años futuristas locos
                        
                        Forms\Components\TextInput::make('price')
                            ->label('Precio de Lista')
                            ->required()
                            ->numeric()
                            ->prefix('$ '), // Pone el signo pesos
                        
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'available' => 'Disponible',
                                'reserved' => 'Reservado',
                                'sold' => 'Vendido',
                            ])
                            ->default('available')
                            ->required(),
                            
                        Forms\Components\FileUpload::make('image_path')
                            ->label('Foto')
                            ->image()
                            ->directory('autos') // Guarda en carpeta 'autos'
                            ->columnSpanFull(),
                    ])->columns(2), // Dos columnas para que quede prolijo
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Foto')
                    ->circular(), // Foto redonda
                
                Tables\Columns\TextColumn::make('brand')
                    ->label('Marca')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('model')
                    ->label('Modelo')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('plate')
                    ->label('Patente')
                    ->weight('bold')
                    ->copyable(), // Te deja copiar la patente con un click
                
                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->money('ARS') // Formato moneda argentina
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success', // Verde
                        'reserved' => 'warning', // Amarillo
                        'sold' => 'danger', // Rojo
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'available' => 'Disponible',
                        'reserved' => 'Reservado',
                        'sold' => 'Vendido',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Filtrar por Estado')
                    ->options([
                        'available' => 'Disponible',
                        'sold' => 'Vendido',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListCars::route('/'),
            'create' => Pages\CreateCar::route('/create'),
            'edit' => Pages\EditCar::route('/{record}/edit'),
        ];
    }
}