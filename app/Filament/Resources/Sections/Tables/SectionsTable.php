<?php

namespace App\Filament\Resources\Sections\Tables;

use App\Models\Task;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Table;

class SectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Проект')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Назва')
                    ->sortable(),
                SelectColumn::make('status')
                    ->label('Статус')
                    ->options(Task::$statuses),
                Tables\Columns\TextColumn::make('asana_gid')
                    ->label('Asana GID')
                    ->copyable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),

                // // Quick action to change status inline (modal with select)
                // Tables\Actions\Action::make('setStatus')
                //     ->label('Змінити статус')
                //     ->icon('heroicon-m-adjustments')
                //     ->form([
                //         Select::make('status')
                //             ->label('Статус')
                //             ->options(Task::$statuses)
                //             ->required(),
                //     ])
                //     ->action(function (\App\Models\Section $record, array $data): void {
                //         $record->update(['status' => $data['status']]);

                //         Notification::make()
                //             ->success()
                //             ->title('Статус змінено')
                //             ->send();
                //     }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
