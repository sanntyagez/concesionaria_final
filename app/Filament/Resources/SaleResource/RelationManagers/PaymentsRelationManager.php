<?php

namespace App\Filament\Resources\SaleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Payment; 
use Filament\Support\Enums\Alignment; // Importante para que no de error la alineación

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Plan de Cuotas';

    protected static ?string $icon = 'heroicon-o-banknotes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('number')
                    ->label('Cuota N°')
                    ->required()
                    ->numeric(),
                
                Forms\Components\TextInput::make('amount')
                    ->label('Monto a Pagar')
                    ->prefix('$')
                    ->required()
                    ->numeric(),

                Forms\Components\DatePicker::make('due_date')
                    ->label('Fecha de Vencimiento')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Cuota')
                    ->formatStateUsing(fn ($state) => "#{$state}") 
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($record) => $record->due_date < now() && !$record->paid_at ? 'danger' : 'gray'),

                // ACÁ ESTÁ EL CAMBIO: Moneda en ARS, alineado a la derecha y SIN el summarize
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('ARS', locale: 'es_AR')
                    ->alignment(Alignment::End),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => $state ? 'PAGADO' : 'PENDIENTE')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->alignment(Alignment::Center),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Método')
                    ->icon(fn ($state) => match ($state) {
                        'efectivo' => 'heroicon-m-banknotes',
                        'transferencia' => 'heroicon-m-computer-desktop',
                        default => 'heroicon-m-question-mark-circle',
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('pendientes')
                    ->query(fn (Builder $query) => $query->whereNull('paid_at'))
                    ->label('Solo Pendientes'),
            ])
            ->headerActions([
                // No permitimos crear cuotas a mano, solo editarlas (cobrar)
            ])
            ->actions([
                // 1. Botón para Editar
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->color('gray'),

                // 2. Botón exclusivo para COBRAR
                Tables\Actions\Action::make('cobrar')
                    ->label('Cobrar')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn (Payment $record) => $record->paid_at === null)
                    ->form([
                        Forms\Components\DatePicker::make('paid_at')
                            ->label('Fecha de Pago')
                            ->default(now())
                            ->required(),
                            
                        Forms\Components\Select::make('payment_method')
                            ->label('Forma de Pago')
                            ->options([
                                'efectivo' => 'Efectivo',
                                'transferencia' => 'Transferencia',
                                'cheque' => 'Cheque',
                            ])
                            ->default('efectivo')
                            ->required(),
                            
                        Forms\Components\TextInput::make('receipt_number')
                            ->label('N° de Recibo (Opcional)'),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->columnSpanFull(),
                    ])
                    ->action(function (Payment $record, array $data): void {
                        $record->update([
                            'paid_at' => $data['paid_at'],
                            'payment_method' => $data['payment_method'],
                            'receipt_number' => $data['receipt_number'],
                            'notes' => $data['notes'],
                        ]);
                    })
                    ->modalHeading('Registrar Cobro de Cuota')
                    ->modalSubmitActionLabel('Confirmar Pago'),

                // 3. Botón para IMPRIMIR RECIBO
                Tables\Actions\Action::make('print_receipt')
                    ->label('Recibo')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (Payment $record) => route('payments.pdf', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Payment $record) => $record->paid_at !== null),
            ])
            ->bulkActions([
                //
            ]);
    }
}