<?php

namespace App\Filament\Resources\ActOfWorks\Tables;

use App\Models\ActOfWork;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActOfWorksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Номер')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->toggleable(),

                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ActOfWork::$type[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        ActOfWork::TYPE_ACT => 'info',
                        ActOfWork::TYPE_RECEIPT_OF_FUNDS => 'success',
                        ActOfWork::TYPE_NEW_PROJECT => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ActOfWork::$statusList[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        ActOfWork::STATUS_PENDING => 'warning',
                        ActOfWork::STATUS_PAID => 'success',
                        ActOfWork::STATUS_DONE => 'success',
                        ActOfWork::STATUS_PARTIALLY_PAID => 'info',
                        ActOfWork::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->sortable()->toggleable(),

                TextColumn::make('user.name')
                    ->label('Користувач')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('period_display')
                    ->label('Період')
                    ->state(function (ActOfWork $record): string {
                        $periodType = $record->period_type ? (ActOfWork::$periodTypeList[$record->period_type] ?? $record->period_type) : '—';
                        $month = $record->period_month ? (ActOfWork::$monthsList[$record->period_month] ?? $record->period_month) : '—';
                        $year = $record->period_year ?? '—';

                        return "{$periodType} ({$month} {$year})";
                    })
                    ->sortable()
                    ->toggleable()
                    ->searchable()->toggleable(),

                TextColumn::make('date')
                    ->label('Дата складання')
                    ->date('d.m.Y')
                    ->sortable()->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Загальна сума')
                    ->money('UAH')
                    ->sortable()
                    ->alignEnd()->toggleable(),

                TextColumn::make('paid_amount')
                    ->label('Оплачено')
                    ->money('UAH')
                    ->sortable()
                    ->alignEnd()
                    ->color(fn (ActOfWork $record): string =>
                        $record->paid_amount >= $record->total_amount ? 'success' : 'warning'
                    )->toggleable(),

                IconColumn::make('file_excel')
                    ->label('Excel')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-arrow-down')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('telegram_status')
                    ->label('Telegram')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ActOfWork::$telegramStatusList[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        ActOfWork::TELEGRAM_STATUS_SEND => 'success',
                        ActOfWork::TELEGRAM_STATUS_FAILED => 'danger',
                        ActOfWork::TELEGRAM_STATUS_PENDING => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Створено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Оновлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options(ActOfWork::$statusList)
                    ->multiple(),

                SelectFilter::make('type')
                    ->label('Тип')
                    ->options(ActOfWork::$type)
                    ->multiple(),

                SelectFilter::make('period_type')
                    ->label('Тип періоду')
                    ->options(ActOfWork::$periodTypeList)
                    ->multiple(),

                SelectFilter::make('period_year')
                    ->label('Рік')
                    ->options(ActOfWork::$yearsList),

                SelectFilter::make('period_month')
                    ->label('Місяць')
                    ->options(ActOfWork::$monthsList),

                SelectFilter::make('user_id')
                    ->label('Користувач')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('telegram_status')
                    ->label('Статус Telegram')
                    ->options(ActOfWork::$telegramStatusList),
            ])
            ->recordActions([
//                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100, 250, 500]);
    }
}
