<?php

namespace App\Filament\Resources\ContractorActOfCompletedWorks\Pages;

use App\Filament\Resources\ContractorActOfCompletedWorks\ContractorActOfCompletedWorkResource;
use App\Imports\PaymentsToContractorActsImport;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListContractorActOfCompletedWorks extends ListRecords
{
    protected static string $resource = ContractorActOfCompletedWorkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importFromCsvXlsx')
                ->label('Імпорт з CSV/XLSX')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->modalHeading('Імпорт актів з CSV або Excel')
                ->modalDescription('Завантажте файл у форматі нижче. Перший рядок — заголовки колонок.')
                ->form([
                    \Filament\Forms\Components\Placeholder::make('import_example')
                        ->label('Приклад таблиці в файлі')
                        ->content(new \Illuminate\Support\HtmlString(
                            '<div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden text-sm">'
                            . '<table class="w-full border-collapse">'
                            . '<thead><tr class="bg-gray-100 dark:bg-gray-800">'
                            . '<th class="border border-gray-200 dark:border-gray-600 px-2 py-1.5 text-left font-medium">Дата документа</th>'
                            . '<th class="border border-gray-200 dark:border-gray-600 px-2 py-1.5 text-left font-medium">Номер документа</th>'
                            . '<th class="border border-gray-200 dark:border-gray-600 px-2 py-1.5 text-left font-medium">Сума</th>'
                            . '<th class="border border-gray-200 dark:border-gray-600 px-2 py-1.5 text-left font-medium">Назва відправника</th>'
                            . '<th class="border border-gray-200 dark:border-gray-600 px-2 py-1.5 text-left font-medium">Призначення платежу</th>'
                            . '</tr></thead><tbody>'
                            . '<tr class="bg-white dark:bg-gray-900"><td class="border border-gray-200 dark:border-gray-600 px-2 py-1.5">18.08.2025</td><td class="border border-gray-200 dark:border-gray-600 px-2 py-1.5">242</td><td class="border border-gray-200 dark:border-gray-600 px-2 py-1.5">18000.00</td><td class="border border-gray-200 dark:border-gray-600 px-2 py-1.5">ТОВ &quot;ІНГСОТ&quot;</td><td class="border border-gray-200 dark:border-gray-600 px-2 py-1.5">За підтримку та доопрацювання веб-сайту Sixt.ua, згідно рахунка № 17</td></tr>'
                            . '<tr class="bg-gray-50 dark:bg-gray-800/50"><td class="border border-gray-200 dark:border-gray-600 px-2 py-1.5">01.08.2025</td><td class="border border-gray-200 dark:border-gray-600 px-2 py-1.5">230</td><td class="border border-gray-200 dark:border-gray-600 px-2 py-1.5">20000.00</td><td class="border border-gray-200 dark:border-gray-600 px-2 py-1.5">ТОВ &quot;ІНГСОТ&quot;</td><td class="border border-gray-200 dark:border-gray-600 px-2 py-1.5">За підтримку веб-сайту yume-honda.com.ua</td></tr>'
                            . '</tbody></table></div>'
                            . '<p class="mt-2 text-gray-600 dark:text-gray-400">CSV: роздільник — кома (,), кодування UTF-8. XLSX: ті самі колонки на першому аркуші.</p>'
                        )),
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->label('Файл CSV або XLSX')
                        ->required()
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/csv',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->disk('local')
                        ->directory('temp-imports')
                        ->visibility('private'),
                ])
                ->action(function (array $data): void {
                    try {
                        $import = new PaymentsToContractorActsImport;
                        Excel::import($import, $data['file']);
                        Storage::disk('local')->delete($data['file']);
                        $message = "✅ Імпорт завершено.\n\n";
                        $message .= "• Створено актів: {$import->getImportedCount()}\n";
                        $message .= "• Пропущено рядків: {$import->getSkippedCount()}\n";
                        if (!empty($import->getWarnings())) {
                            $message .= "\n⚠️ Попередження:\n" . implode("\n", array_slice($import->getWarnings(), 0, 8));
                            if (count($import->getWarnings()) > 8) {
                                $message .= "\n... та ще " . (count($import->getWarnings()) - 8);
                            }
                        }
                        if (!empty($import->getErrors())) {
                            $message .= "\n\n❌ Помилки:\n" . implode("\n", array_slice($import->getErrors(), 0, 5));
                            if (count($import->getErrors()) > 5) {
                                $message .= "\n... та ще " . (count($import->getErrors()) - 5);
                            }
                        }
                        Notification::make()
                            ->title(!empty($import->getErrors()) ? 'Імпорт виконано з помилками' : 'Імпорт виконано')
                            ->body($message)
                            ->success()
                            ->duration(10000)
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Помилка імпорту')
                            ->body('❌ ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            CreateAction::make(),
        ];
    }
}
