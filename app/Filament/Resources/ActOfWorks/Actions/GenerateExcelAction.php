<?php

namespace App\Filament\Resources\ActOfWorks\Actions;

use App\Exports\ActOfWorkExport;
use App\Models\ActOfWork;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;

class GenerateExcelAction
{
    public static function make(): Action
    {
        return Action::make('generateExcel')
            ->label('Генерувати Excel')
            ->icon('heroicon-o-document-arrow-down')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Генерувати Excel файл')
            ->modalDescription('Створити новий Excel файл для цього акту робіт?')
            ->modalSubmitActionLabel('Генерувати')
            ->action(function (ActOfWork $record): void {
                try {
                    // Генеруємо унікальне ім'я файлу
                    $filename = 'act-'.$record->number.'-'.now()->format('Y-m-d-His').'.xlsx';
                    $path = 'act-of-works/'.$filename;

                    // Зберігаємо Excel файл
                    Excel::store(
                        new ActOfWorkExport($record),
                        $path,
                        'public'
                    );

                    // Оновлюємо запис з шляхом до файлу
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
            });
    }
}
