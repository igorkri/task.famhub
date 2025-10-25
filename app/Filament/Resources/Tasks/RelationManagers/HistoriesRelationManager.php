<?php

namespace App\Filament\Resources\Tasks\RelationManagers;

use App\Models\TaskHistory;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    protected static ?string $title = 'Історія змін';

    protected static ?string $label = 'зміна';

    protected static ?string $pluralLabel = 'історія змін';

    public function form(Schema $schema): Schema
    {
        // Форма не потрібна, оскільки історія тільки для читання
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('event_at', 'desc')
            ->columns([
                TextColumn::make('event_at')
                    ->label('Дата і час')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->size('sm'),

                IconColumn::make('source')
                    ->label('Джерело')
                    ->icon(fn (string $state): string => match ($state) {
                        TaskHistory::SOURCE_LOCAL => 'heroicon-o-computer-desktop',
                        TaskHistory::SOURCE_ASANA_WEBHOOK => 'heroicon-o-globe-alt',
                        TaskHistory::SOURCE_ASANA_SYNC => 'heroicon-o-arrow-path',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): array => match ($state) {
                        TaskHistory::SOURCE_LOCAL => Color::Blue,
                        TaskHistory::SOURCE_ASANA_WEBHOOK => Color::Purple,
                        TaskHistory::SOURCE_ASANA_SYNC => Color::Teal,
                        default => Color::Gray,
                    })
                    ->tooltip(fn (string $state): string => match ($state) {
                        TaskHistory::SOURCE_LOCAL => 'Локальна зміна',
                        TaskHistory::SOURCE_ASANA_WEBHOOK => 'Webhook з Asana',
                        TaskHistory::SOURCE_ASANA_SYNC => 'Синхронізація з Asana',
                        default => 'Невідоме джерело',
                    })
                    ->size('sm'),

                TextColumn::make('event_type')
                    ->label('Подія')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        TaskHistory::EVENT_CREATED => '🆕 Створено',
                        TaskHistory::EVENT_UPDATED => '✏️ Оновлено',
                        TaskHistory::EVENT_DELETED => '🗑️ Видалено',
                        TaskHistory::EVENT_STATUS_CHANGED => '🔄 Зміна статусу',
                        TaskHistory::EVENT_ASSIGNED => '👤 Призначено',
                        TaskHistory::EVENT_UNASSIGNED => '👤 Зняте призначення',
                        TaskHistory::EVENT_SECTION_CHANGED => '📁 Зміна секції',
                        TaskHistory::EVENT_PRIORITY_CHANGED => '⚡ Зміна пріоритету',
                        TaskHistory::EVENT_DEADLINE_CHANGED => '📅 Зміна дедлайну',
                        TaskHistory::EVENT_COMPLETED => '✅ Завершено',
                        TaskHistory::EVENT_REOPENED => '🔓 Відновлено',
                        TaskHistory::EVENT_COMMENT_ADDED => '💬 Коментар',
                        TaskHistory::EVENT_ATTACHMENT_ADDED => '📎 Файл',
                        TaskHistory::EVENT_CUSTOM_FIELD_CHANGED => '🏷️ Кастомне поле',
                        default => ucfirst(str_replace('_', ' ', $state)),
                    })
                    ->badge()
                    ->color(fn (string $state): array => match ($state) {
                        TaskHistory::EVENT_CREATED => Color::Green,
                        TaskHistory::EVENT_DELETED => Color::Red,
                        TaskHistory::EVENT_STATUS_CHANGED => Color::Blue,
                        TaskHistory::EVENT_ASSIGNED, TaskHistory::EVENT_UNASSIGNED => Color::Amber,
                        TaskHistory::EVENT_COMPLETED => Color::Emerald,
                        TaskHistory::EVENT_REOPENED => Color::Orange,
                        TaskHistory::EVENT_PRIORITY_CHANGED => Color::Purple,
                        default => Color::Gray,
                    })
                    ->searchable()
                    ->size('sm'),

                TextColumn::make('field_name')
                    ->label('Поле')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'status' => '📊 Статус',
                        'user_id' => '👤 Виконавець',
                        'section_id' => '📁 Секція',
                        'priority' => '⚡ Пріоритет',
                        'deadline' => '📅 Дедлайн',
                        'is_completed' => '✅ Завершено',
                        'title' => '📝 Назва',
                        'description' => '📄 Опис',
                        default => $state ? ucfirst($state) : '-',
                    })
                    ->toggleable()
                    ->size('sm'),

                TextColumn::make('old_value')
                    ->label('Було')
                    ->limit(30)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->toggleable()
                    ->size('sm'),

                TextColumn::make('new_value')
                    ->label('Стало')
                    ->limit(30)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->weight('bold')
                    ->toggleable()
                    ->size('sm'),

                TextColumn::make('user.name')
                    ->label('Користувач')
                    ->default('-')
                    ->toggleable()
                    ->size('sm'),

                TextColumn::make('description')
                    ->label('Опис')
                    ->limit(50)
                    ->tooltip(fn (?string $state): ?string => $state)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->size('sm'),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Тип події')
                    ->multiple()
                    ->options([
                        TaskHistory::EVENT_CREATED => 'Створено',
                        TaskHistory::EVENT_UPDATED => 'Оновлено',
                        TaskHistory::EVENT_DELETED => 'Видалено',
                        TaskHistory::EVENT_STATUS_CHANGED => 'Зміна статусу',
                        TaskHistory::EVENT_ASSIGNED => 'Призначено',
                        TaskHistory::EVENT_UNASSIGNED => 'Зняте призначення',
                        TaskHistory::EVENT_SECTION_CHANGED => 'Зміна секції',
                        TaskHistory::EVENT_PRIORITY_CHANGED => 'Зміна пріоритету',
                        TaskHistory::EVENT_DEADLINE_CHANGED => 'Зміна дедлайну',
                        TaskHistory::EVENT_COMPLETED => 'Завершено',
                        TaskHistory::EVENT_REOPENED => 'Відновлено',
                        TaskHistory::EVENT_COMMENT_ADDED => 'Коментар',
                        TaskHistory::EVENT_ATTACHMENT_ADDED => 'Файл',
                        TaskHistory::EVENT_CUSTOM_FIELD_CHANGED => 'Кастомне поле',
                    ]),

                SelectFilter::make('source')
                    ->label('Джерело')
                    ->options([
                        TaskHistory::SOURCE_LOCAL => 'Локальна зміна',
                        TaskHistory::SOURCE_ASANA_WEBHOOK => 'Webhook з Asana',
                        TaskHistory::SOURCE_ASANA_SYNC => 'Синхронізація з Asana',
                    ]),
            ])
            ->headerActions([
                // Без дій - тільки читання
            ])
            ->recordActions([
                // Без дій - тільки читання
            ])
            ->bulkActions([
                // Без масових дій
            ])
            ->paginated([10, 25, 50, 100]);
    }
}
