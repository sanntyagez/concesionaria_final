<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\Car; // Importante para buscar autos
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get; // Para leer valores en vivo
use Filament\Forms\Set; // Para escribir valores en vivo
use App\Filament\Resources\SaleResource\RelationManagers\PaymentsRelationManager;
use Illuminate\Database\Eloquent\Builder;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar'; // Ícono de billete
    
    protected static ?string $navigationLabel = 'Ventas';

    protected static ?string $modelLabel = 'Venta';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles de la Operación')
                    ->schema([
                        // Selección de Cliente
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'name')
                            ->label('Cliente')
                            ->searchable()
                            ->preload()
                            ->required(),

                        // Selección de Auto (Solo muestra los DISPONIBLES)
                        Forms\Components\Select::make('car_id')
                            ->label('Vehículo')
                            ->options(Car::where('status', 'available')->pluck('model', 'id')) // Muestra Modelo
                            ->searchable()
                            ->required()
                            ->reactive() // Si cambia, busca el precio
                            ->afterStateUpdated(function ($state, Set $set) {
                                $car = Car::find($state);
                                if ($car) {
                                    $set('total_amount', $car->price); // Pone el precio solo
                                }
                            }),

                        // --- CALCULADORA ---
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Valor de Venta')
                            ->prefix('$')
                            ->numeric()
                            ->required()
                            ->reactive(), // Reactivo para recalcular cuotas

                        Forms\Components\TextInput::make('down_payment')
                            ->label('Entrega Inicial (Anticipo)')
                            ->prefix('$')
                            ->numeric()
                            ->default(0)
                            ->reactive(), // Reactivo

                        Forms\Components\Select::make('payment_method')
                            ->label('Forma de Pago (Entrega)')
                            ->options([
                                'efectivo' => 'Efectivo',
                                'transferencia' => 'Transferencia',
                                'cheque' => 'Cheque',
                            ])
                            ->default('efectivo')
                            ->required(),

                        Forms\Components\DatePicker::make('sale_date')
                            ->label('Fecha de Venta')
                            ->default(now())
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Financiación')
                    ->description('Configura las cuotas. El sistema calculará el valor automáticamente.')
                    ->schema([
                        Forms\Components\TextInput::make('installments_count')
                            ->label('Cantidad de Cuotas')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->reactive(), // Reactivo

                        Forms\Components\TextInput::make('interest_rate')
                            ->label('Interés (%)')
                            ->suffix('%')
                            ->numeric()
                            ->default(0)
                            ->reactive(), // Reactivo

                        // CAMPO MÁGICO: Muestra cuánto va a pagar por mes
                        Forms\Components\TextInput::make('installment_value')
                            ->label('Valor Estimado de Cuota')
                            ->prefix('$')
                            ->readOnly() // No se edita a mano, se calcula solo
                            ->dehydrated() // Se guarda en la BD
                            ->reactive()
                            ->afterStateHydrated(function (Set $set, Get $get) {
                                self::calculateInstallment($get, $set);
                            })
                            // Escucha cambios en los otros campos
                            ->key('installment_calulator'),
                            
                        Forms\Components\Textarea::make('guarantor_info')
                            ->label('Datos del Garante (Opcional)')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    // Función auxiliar para calcular la cuota en vivo
    public static function calculateInstallment(Get $get, Set $set): void
    {
        $total = floatval($get('total_amount'));
        $down = floatval($get('down_payment'));
        $cuotas = intval($get('installments_count'));
        $interes = floatval($get('interest_rate'));

        if ($cuotas > 0) {
            $saldo = $total - $down;
            
            // Si hay saldo a financiar
            if ($saldo > 0) {
                // Aplicamos interés simple al saldo: (Saldo + %) / Cuotas
                $montoConInteres = $saldo * (1 + ($interes / 100));
                $valorCuota = $montoConInteres / $cuotas;
                
                $set('installment_value', number_format($valorCuota, 2, '.', ''));
            } else {
                $set('installment_value', 0);
            }
        }
    }

   public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sale_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Cliente')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('car.model')
                    ->label('Auto')
                    ->description(fn (Sale $record): string => $record->car->plate),
                
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Venta')
                    ->money('ARS'),
                
                // Progreso de Cuotas (Ej: 12 / 1)
                Tables\Columns\TextColumn::make('installments_progress')
                    ->label('Cuotas (Total/Pagas)')
                    ->getStateUsing(function (Sale $record) {
                        $pagadas = $record->payments()->whereNotNull('paid_at')->count();
                        return $record->installments_count . ' / ' . $pagadas;
                    })
                    ->badge()
                    ->color(fn (Sale $record) => 
                        $record->payments()->whereNotNull('paid_at')->count() >= $record->installments_count ? 'success' : 'info'
                    ),

                // Estado (Al día / Atrasado)
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Estado')
                    ->getStateUsing(function (Sale $record) {
                        $atrasado = $record->payments()
                            ->whereNull('paid_at')
                            ->whereDate('due_date', '<', now())
                            ->exists();
                        return $atrasado ? 'Atrasado' : 'Al día';
                    })
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Atrasado' ? 'danger' : 'success')
                    ->icon(fn (string $state): string => $state === 'Atrasado' ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle'),
            ])
            ->filters([
                // --- AQUÍ ESTÁ TU FILTRO NUEVO ---
                Tables\Filters\SelectFilter::make('estado_pago')
                    ->label('Filtrar por Estado')
                    ->options([
                        'al_dia' => '✅ Al Día',
                        'atrasado' => '⚠️ Atrasado',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'atrasado') {
                            return $query->whereHas('payments', function ($q) {
                                $q->whereNull('paid_at')->whereDate('due_date', '<', now());
                            });
                        }
                        if ($data['value'] === 'al_dia') {
                            return $query->whereDoesntHave('payments', function ($q) {
                                $q->whereNull('paid_at')->whereDate('due_date', '<', now());
                            });
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
        PaymentsRelationManager::class,
          ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}