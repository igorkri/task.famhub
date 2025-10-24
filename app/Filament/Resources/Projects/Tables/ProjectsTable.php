<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Jobs\SyncProjectAsanaTasks;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')
                    ->label('Назва')
                    ->searchable(),
                TextColumn::make('workspace.name')
                    ->label('Робоче пространство')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Активний проект'),
                ToggleColumn::make('is_favorite')
                    ->label('Вибране'),
                ToggleColumn::make('is_archived')
                    ->label('Архівований'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort')
            ->reorderable('sort')
            ->filters([
                //
            ])
            ->recordActions([
                //                ViewAction::make(),
                EditAction::make(),
                Action::make('sync_asana')
                    ->label('Sync Asana')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function (Project $record) {
                        $project = $record;

                        $asanaProjectId = $project->asana_id ?? null;
                        if (! $asanaProjectId) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Відсутній Asana project id')
                                ->body('Для цього проєкту не налаштовано Asana ID.')
                                ->send();

                            return;
                        }

                        // Dispatch job to queue
                        SyncProjectAsanaTasks::dispatch($project);
                        \Filament\Notifications\Notification::make()
                            ->info()
                            ->title('Синхронізацію поставлено в чергу')
                            ->body('Синхронізація проєкту поставлена в чергу та незабаром буде виконана.')
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
