<?php

namespace App\Filament\Resources\Books\Tables;

use App\Models\Book;
use App\Models\Loan;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BooksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('author')
                    ->label('Penulis')
                    ->searchable(),

                TextColumn::make('publisher')
                    ->label('Penerbit')
                    ->searchable(),

                TextColumn::make('year')
                    ->label('Tahun')
                    ->sortable(),

                TextColumn::make('stock')
                    ->label('Stok')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                Action::make('pinjam')
                    ->icon('heroicon-o-book-open')
                    ->requiresConfirmation()
                    ->visible(fn () => ! auth()->user()->isAdmin())
                    ->disabled(fn (Book $record) => $record->stock < 1)
                    ->action(function (Book $record) {
                        $aktif = Loan::where('user_id', auth()->id())
                            ->where('status', 'dipinjam')
                            ->count();

                        if ($aktif >= 3) {
                            Notification::make()
                                ->title('Maksimal pinjam 3 buku')
                                ->danger()
                                ->send();

                            return;
                        }

                        Loan::create([
                            'user_id' => auth()->id(),
                            'book_id' => $record->id,
                            'loan_date' => now(),
                            'due_date' => now()->addDays(7),
                        ]);

                        $record->decrement('stock');

                        Notification::make()
                            ->title('Buku berhasil dipinjam')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}