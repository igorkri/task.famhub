<?php

namespace App\Filament\Resources\ActOfWorks\Tables;

use App\Exports\ActOfWorkExport;
use App\Models\ActOfWork;
use App\Services\TelegramService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

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
                    ->sortable()
                    ->toggleable(),

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
                    ->searchable(),

                TextColumn::make('date')
                    ->label('Дата складання')
                    ->date('d.m.Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label('Загальна сума')
                    ->money('UAH')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),

                TextColumn::make('paid_amount')
                    ->label('Оплачено')
                    ->money('UAH')
                    ->sortable()
                    ->alignEnd()
                    ->color(fn (ActOfWork $record): string => $record->paid_amount >= $record->total_amount ? 'success' : 'warning')
                    ->toggleable(),

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
                    ->options(function () {
                        return \App\Models\User::usersList();
                    })
                    ->searchable()
                    ->preload(),

                SelectFilter::make('telegram_status')
                    ->label('Статус Telegram')
                    ->options(ActOfWork::$telegramStatusList),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('generateExcel')
                        ->label('Генерувати Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Генерувати Excel файл')
                        ->modalDescription('Створити новий Excel файл для цього акту робіт?')
                        ->modalSubmitActionLabel('Генерувати')
                        ->action(function (ActOfWork $record): void {
                            try {
                                $filename = 'act-'.$record->number.'-'.now()->format('Y-m-d-His').'.xlsx';
                                $path = 'act-of-works/'.$filename;

                                Excel::store(
                                    new ActOfWorkExport($record),
                                    $path,
                                    'public'
                                );

                                $record->update([
                                    'file_excel' => $path,
                                ]);

                                Notification::make()
                                    ->success()
                                    ->title('Excel файл згенеровано!')
                                    ->body("Файл {$filename} успішно створено")
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Помилка генерації Excel')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),
                    Action::make('sendToTelegram')
                        ->label('Надіслати в Telegram')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Надіслати в Telegram')
                        ->modalDescription('Надіслати Excel файл цього акту робіт в Telegram?')
                        ->modalSubmitActionLabel('Надіслати')
                        ->visible(fn (ActOfWork $record): bool => ! empty($record->file_excel))
                        ->action(function (ActOfWork $record): void {
                            self::sendToTelegram($record);
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulkGenerateExcel')
                        ->label('Генерувати Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Генерувати Excel файли')
                        ->modalDescription('Створити Excel файли для всіх вибраних актів робіт?')
                        ->modalSubmitActionLabel('Генерувати')
                        ->action(function (Collection $records): void {
                            $success = 0;
                            $errors = 0;

                            foreach ($records as $record) {
                                try {
                                    $filename = 'act-'.$record->number.'-'.now()->format('Y-m-d-His').'.xlsx';
                                    $path = 'act-of-works/'.$filename;

                                    Excel::store(
                                        new ActOfWorkExport($record),
                                        $path,
                                        'public'
                                    );

                                    $record->update([
                                        'file_excel' => $path,
                                    ]);

                                    $success++;
                                } catch (\Exception $e) {
                                    $errors++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Excel файли згенеровано!')
                                ->body("Успішно: {$success}, Помилок: {$errors}")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('bulkSendToTelegram')
                        ->label('Надіслати в Telegram')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Надіслати в Telegram')
                        ->modalDescription('Надіслати Excel файли всіх вибраних актів в Telegram?')
                        ->modalSubmitActionLabel('Надіслати')
                        ->action(function (Collection $records): void {
                            $success = 0;
                            $errors = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                if (empty($record->file_excel)) {
                                    $skipped++;

                                    continue;
                                }

                                $result = self::sendToTelegram($record, false);
                                if ($result) {
                                    $success++;
                                } else {
                                    $errors++;
                                }
                            }

                            $message = "Надіслано: {$success}";
                            if ($errors > 0) {
                                $message .= ", Помилок: {$errors}";
                            }
                            if ($skipped > 0) {
                                $message .= ", Пропущено (без Excel): {$skipped}";
                            }

                            Notification::make()
                                ->success()
                                ->title('Надсилання в Telegram завершено!')
                                ->body($message)
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100, 250, 500]);
    }

    /**
     * Надсилає акт в Telegram
     */
    protected static function sendToTelegram(ActOfWork $record, bool $showNotification = true): bool
    {
        try {
            $telegramService = app(TelegramService::class);

            if (empty($record->file_excel)) {
                $record->update([
                    'telegram_status' => ActOfWork::TELEGRAM_STATUS_FAILED,
                ]);

                if ($showNotification) {
                    Notification::make()
                        ->warning()
                        ->title('Файл Excel відсутній!')
                        ->body('Спочатку згенеруйте Excel файл для цього акту')
                        ->send();
                }

                return false;
            }

            $filePath = Storage::disk('public')->path($record->file_excel);

            if (! file_exists($filePath)) {
                $record->update([
                    'telegram_status' => ActOfWork::TELEGRAM_STATUS_FAILED,
                ]);

                if ($showNotification) {
                    Notification::make()
                        ->danger()
                        ->title('Файл не знайдено!')
                        ->body("Файл {$record->file_excel} не існує на сервері")
                        ->send();
                }

                return false;
            }

            $periodType = ActOfWork::$periodTypeList[$record->period_type] ?? $record->period_type;
            $periodMonth = ActOfWork::$monthsList[$record->period_month] ?? $record->period_month;
            $periodYear = $record->period_year ?? '';
            $date = $record->date?->format('d.m.Y') ?? '';

            $title = "🧾 Звіт {$periodType} {$periodMonth} {$periodYear}\n"
                ."📅 Дата складання: {$date}\n"
                ."📋 № {$record->number}\n"
                .'💰 Сума: '.number_format((float) $record->total_amount, 2).' грн';

            $result = $telegramService->sendDocument(
                $filePath,
                $title
            );

            if ($result) {
                $record->update([
                    'telegram_status' => ActOfWork::TELEGRAM_STATUS_SEND,
                ]);

                if ($showNotification) {
                    Notification::make()
                        ->success()
                        ->title('Успішно надіслано в Telegram!')
                        ->body("Акт №{$record->number} надіслано")
                        ->send();
                }

                return true;
            } else {
                $record->update([
                    'telegram_status' => ActOfWork::TELEGRAM_STATUS_FAILED,
                ]);

                if ($showNotification) {
                    Notification::make()
                        ->danger()
                        ->title('Помилка надсилання')
                        ->body('Не вдалося надіслати файл в Telegram')
                        ->send();
                }

                return false;
            }
        } catch (\Exception $e) {
            $record->update([
                'telegram_status' => ActOfWork::TELEGRAM_STATUS_FAILED,
            ]);

            if ($showNotification) {
                Notification::make()
                    ->danger()
                    ->title('Помилка надсилання в Telegram')
                    ->body($e->getMessage())
                    ->send();
            }

            return false;
        }
    }
}
