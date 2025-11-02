<?php

namespace App\Filament\Resources\ActOfWorks\Pages;

use App\Filament\Resources\ActOfWorks\ActOfWorkResource;
use App\Filament\Resources\ActOfWorks\Widgets\ActOfWorkStatsWidget;
use App\Imports\ReceiptOfFundsCsvImport;
use App\Imports\ReceiptOfFundsImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListActOfWorks extends ListRecords
{
    protected static string $resource = ActOfWorkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //            Action::make('importReceiptOfFunds')
            //                ->label('Імпорт надходжень коштів')
            //                ->icon('heroicon-o-arrow-up-tray')
            //                ->color('success')
            //                ->form([
            //                    FileUpload::make('file')
            //                        ->label('Файл Excel')
            //                        ->required()
            //                        ->acceptedFileTypes([
            //                            'application/vnd.ms-excel',
            //                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            //                            'application/vnd.oasis.opendocument.spreadsheet',
            //                        ])
            //                        ->helperText('Формат файлу: XLS, XLSX або ODS. Повинен містити колонки: номер, дата, сума, користувач (опціонально)')
            //                        ->disk('local')
            //                        ->directory('temp-imports')
            //                        ->visibility('private'),
            //                ])
            //                ->action(function (array $data): void {
            //                    try {
            //                        $import = new ReceiptOfFundsImport;
            //                        Excel::import($import, $data['file']);
            //                        Storage::disk('local')->delete($data['file']);
            //                        $message = "Імпорт завершено! Імпортовано: {$import->getImportedCount()}, Пропущено: {$import->getSkippedCount()}";
            //                        if ($import->getErrors()) {
            //                            $message .= "\n\nПомилки:\n".implode("\n", $import->getErrors());
            //                        }
            //                        Notification::make()
            //                            ->title('Імпорт виконано')
            //                            ->body($message)
            //                            ->success()
            //                            ->send();
            //                    } catch (\Exception $e) {
            //                        Notification::make()
            //                            ->title('Помилка імпорту')
            //                            ->body($e->getMessage())
            //                            ->danger()
            //                            ->send();
            //                    }
            //                }),
            Action::make('importReceiptOfFundsCsv')
                ->label('Імпорт надходжень (CSV з перевіркою)')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->form([
                    FileUpload::make('file')
                        ->label('Файл CSV')
                        ->required()
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/csv',
                            'application/vnd.ms-excel',
                        ])
                        ->helperText('CSV файл з перевіркою стовпців. Обов\'язкові колонки: nomer/number, data/date, suma/amount')
                        ->disk('local')
                        ->directory('temp-imports')
                        ->visibility('private'),
                ])
                ->action(function (array $data): void {
                    try {
                        $import = new ReceiptOfFundsCsvImport;
                        Excel::import($import, $data['file']);
                        Storage::disk('local')->delete($data['file']);
                        $message = "✅ Імпорт завершено!\n\n";
                        $message .= "📊 Статистика:\n";
                        $message .= "• Імпортовано: {$import->getImportedCount()}\n";
                        $message .= "• Пропущено: {$import->getSkippedCount()}\n";
                        if ($import->getWarnings()) {
                            $message .= "\n⚠️ Попередження:\n".implode("\n", array_slice($import->getWarnings(), 0, 5));
                            if (count($import->getWarnings()) > 5) {
                                $message .= "\n... та ще ".(count($import->getWarnings()) - 5).' попереджень';
                            }
                        }
                        if ($import->getErrors()) {
                            $message .= "\n\n❌ Помилки:\n".implode("\n", array_slice($import->getErrors(), 0, 5));
                            if (count($import->getErrors()) > 5) {
                                $message .= "\n... та ще ".(count($import->getErrors()) - 5).' помилок';
                            }
                        }
                        Notification::make()
                            ->title($import->getErrors() ? 'Імпорт виконано з помилками' : 'Імпорт виконано успішно')
                            ->body($message)
                            ->success()
                            ->duration(10000)
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Помилка імпорту')
                            ->body("❌ {$e->getMessage()}")
                            ->danger()
                            ->send();
                    }
                }),
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ActOfWorkStatsWidget::class,
        ];
    }
}
