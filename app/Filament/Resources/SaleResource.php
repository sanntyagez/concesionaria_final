<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\Car; 
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get; 
use Filament\Forms\Set; 
use App\Filament\Resources\SaleResource\RelationManagers\PaymentsRelationManager;
use Illuminate\Database\Eloquent\Builder;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar'; 
    
    protected static ?string $navigationLabel = 'Ventas';

    protected static ?string $modelLabel = 'Venta';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles de la Operación')
                    ->schema([
                        // --- 1. CLIENTE ---
                        Forms\Components\Select::make('client_id')
                            ->label('Cliente *')
                            ->relationship('client', 'name') 
                            ->searchable(['name', 'dni']) 
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} - DNI: {$record->dni}")
                            ->placeholder('Buscar por nombre o DNI...') 
                            ->required(),

                        // --- 2. AUTO ---
                        Forms\Components\Select::make('car_id')
                            ->label('Vehículo *')
                            ->relationship('car', 'model', fn (Builder $query) => $query->where('status', 'available'))
                            ->searchable(['model', 'brand', 'plate']) 
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->brand} {$record->model} - Patente: {$record->plate}")
                            ->placeholder('Buscar por marca, modelo o patente...')
                            ->required()
                            ->reactive() 
                            ->afterStateUpdated(function ($state, Set $set) {
                                $car = Car::find($state);
                                if ($car) {
                                    $set('total_amount', $car->price); 
                                }
                            }),

                        // --- CALCULADORA ---
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Valor de Venta')
                            ->prefix('$')
                            ->numeric()
                            ->required()
                            ->reactive(), 

                        Forms\Components\TextInput::make('down_payment')
                            ->label('Entrega Inicial (Anticipo)')
                            ->prefix('$')
                            ->numeric()
                            ->default(0)
                            ->reactive(), 

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
                            ->reactive(), 

                        Forms\Components\TextInput::make('interest_rate')
                            ->label('Interés (%)')
                            ->suffix('%')
                            ->numeric()
                            ->default(0)
                            ->reactive(), 

                        Forms\Components\TextInput::make('installment_value')
                            ->label('Valor Estimado de Cuota')
                            ->prefix('$')
                            ->readOnly() 
                            ->dehydrated() 
                            ->reactive()
                            ->afterStateHydrated(function (Set $set, Get $get) {
                                self::calculateInstallment($get, $set);
                            })
                            ->key('installment_calulator'),
                            
                        Forms\Components\Textarea::make('guarantor_info')
                            ->label('Datos del Garante (Opcional)')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(3),
            ]);
    }

    public static function calculateInstallment(Get $get, Set $set): void
    {
        $total = floatval($get('total_amount'));
        $down = floatval($get('down_payment'));
        $cuotas = intval($get('installments_count'));
        $interes = floatval($get('interest_rate'));

        if ($cuotas > 0) {
            $saldo = $total - $down;
            
            if ($saldo > 0) {
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