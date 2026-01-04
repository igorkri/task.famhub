<?php

namespace App\Filament\Resources\ActOfWorks\Actions;

use App\Models\ActOfWork;
use App\Services\TelegramService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class SendToTelegramAction
{
    public static function make(): Action
    {
        return Action::make('sendToTelegram')
            ->label('Надіслати в Telegram')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Надіслати в Telegram')
            ->modalDescription('Надіслати Excel файл цього акту робіт в Telegram?')
            ->modalSubmitActionLabel('Надіслати')
            ->visible(fn (ActOfWork $record): bool => ! empty($record->file_excel))
            ->action(function (ActOfWork $record): void {
                try {
                    $telegramService = app(TelegramService::class);

                    if (empty($record->file_excel)) {
                        $record->update([
                            'telegram_status' => ActOfWork::TELEGRAM_STATUS_FAILED,
                        ]);

                        Notification::make()
                            ->warning()
                            ->title('Файл Excel відсутній!')
                            ->body('Спочатку згенеруйте Excel файл для цього акту')
                            ->send();

                        return;
                    }

                    // Отримуємо повний шлях до файлу
                    $filePath = Storage::disk('public')->path($record->file_excel);

                    if (! file_exists($filePath)) {
                        $record->update([
                            'telegram_status' => ActOfWork::TELEGRAM_STATUS_FAILED,
                        ]);

                        Notification::make()
                            ->danger()
                            ->title('Файл не знайдено!')
                            ->body("Файл {$record->file_excel} не існує на сервері")
                            ->send();

                        return;
                    }

                    // Формуємо заголовок повідомлення
                    $periodType = ActOfWork::$periodTypeList[$record->period_type] ?? $record->period_type;
                    $periodMonth = ActOfWork::$monthsList[$record->period_month] ?? $record->period_month;
                    $periodYear = $record->period_year ?? '';
                    $date = $record->date?->format('d.m.Y') ?? '';

                    $title = "🧾 Звіт {$periodType} {$periodMonth} {$periodYear}\n"
                        ."📅 Дата складання: {$date}\n"
                        ."📋 № {$record->number}\n"
                        .'💰 Сума: '.number_format((float) $record->total_amount, 2).' грн';

                    // Надсилаємо документ
                    $result = $telegramService->sendDocument(
                        $filePath,
                        $title
                    );

                    if ($result) {
                        $record->update([
                            'telegram_status' => ActOfWork::TELEGRAM_STATUS_SEND,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Успішно надіслано в Telegram!')
                            ->body("Акт №{$record->number} надіслано")
                            ->send();
                    } else {
                        $record->update([
                            'telegram_status' => ActOfWork::TELEGRAM_STATUS_FAILED,
                        ]);

                        Notification::make()
                            ->danger()
                            ->title('Помилка надсилання')
                            ->body('Не вдалося надіслати файл в Telegram')
                            ->send();
                    }
                } catch (\Exception $e) {
                    $record->update([
                        'telegram_status' => ActOfWork::TELEGRAM_STATUS_FAILED,
                    ]);

                    Notification::make()
                        ->danger()
                        ->title('Помилка надсилання в Telegram')
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
