<?php

namespace App\Filament\Resources\Loans\Tables;

use App\Models\Loan;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Peminjam')
                    ->searchable(),

                TextColumn::make('book.title')
                    ->label('Buku')
                    ->searchable(),

                TextColumn::make('loan_date')
                    ->label('Tanggal Pinjam')
                    ->date(),

                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date(),

                TextColumn::make('returned_at')
                    ->label('Tanggal Kembali')
                    ->date()
                    ->placeholder('-'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string =>
                        $state === 'dipinjam' ? 'warning' : 'success'
                    ),

                TextColumn::make('fine')
                    ->label('Denda')
                    ->numeric(),
            ])
            ->filters([])
            ->recordActions([
                Action::make('kembalikan')
                    ->label('Kembalikan')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->visible(fn (Loan $record): bool =>
                        $record->status === 'dipinjam'
                    )
                    ->action(function (Loan $record): void {
                        $telat = (int) $record->due_date
                            ->diffInDays(now()->startOfDay(), false);

                        $record->update([
                            'returned_at' => now(),
                            'status' => 'dikembalikan',
                            'fine' => max(0, $telat) * 1000,
                        ]);

                        $record->book()->increment('stock');

                        Notification::make()
                            ->title('Buku sudah dikembalikan')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}