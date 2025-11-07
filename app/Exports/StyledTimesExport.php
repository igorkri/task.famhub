<?php

namespace App\Exports;

use App\Models\Time;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class StyledTimesExport extends ExcelExport implements WithEvents, WithStyles
{
    public function setUp(): void
    {
        $this->withFilename(fn () => date('Y-m-d').' - Звіт_Times');
        $this->withColumns([
            Column::make('created_at')->heading('Дата створення завдання')->formatStateUsing(fn ($state, $record) => $record->task?->created_at?->format('d.m.Y H:i') ?? $state?->format('d.m.Y H:i')),
            Column::make('title')->heading('Назва задачі')->formatStateUsing(fn ($state, $record) => $record->task?->title ?? $state),
            //            Column::make('task.project.name')->heading('Проект'),
            Column::make('empty_1')->heading(''),
            Column::make('empty_2')->heading(''),
            Column::make('empty_3')->heading(''),
            Column::make('id')->heading('Трекер часу')
                ->formatStateUsing(fn ($state, $record) => $record->getDateRangeFormatted()),

            Column::make('duration')
                ->heading('Час, хв')
                ->formatStateUsing(fn ($state) => number_format($state / 60, 2, '.', '')),
            Column::make('coefficient')->heading('Коефіцієнт'),

            //            Column::make('calculated_amount')
            //                ->heading('Ціна')
            //                ->formatStateUsing(fn ($state, $record) => number_format(
            //                    $record->duration / 3600 * $record->coefficient * Time::PRICE,
            //                    2,
            //                    '.',
            //                    ''
            //                )),
            Column::make('description')
                ->heading('Коментар'),

            Column::make('task.permalink_url')
                ->heading('Посилання на задачу')
                ->formatStateUsing(fn ($state, $record) => $record->task?->permalink_url ? '=HYPERLINK("'.$record->task->permalink_url.'", "'.$record->task?->gid.'")' : ''),
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        return [
            // Стиль заголовків (рядок 1)
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    //                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    //                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        //                        'color' => ['rgb' => '000000'],
                    ],
                ],
                // Висота рядка заголовків
            ],

            // Рамки для всіх комірок з даними
            "A1:{$highestColumn}{$highestRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],

            // Вирівнювання числових колонок
            // C - Час (по центру)
            "C2:C{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            // D - Коефіцієнт (по центру)
            "D2:D{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            // E - Ціна (праворуч)
            "E2:E{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
            ],
            // H - Посилання (по центру, синій, підкреслений)
            "H2:H{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'font' => [
                    //                    'color' => ['rgb' => '0563C1'],
                    'underline' => true,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Получаем записи напрямую из базы данных
                // Предполагаем, что экспорт использует тот же query, что и таблица
                $query = $this->getQuery();

                if (! $query) {
                    return;
                }

                $records = $query->get();

                // Начинаем со второй строки (первая - заголовки)
                $rowNumber = 2;

                foreach ($records as $record) {
                    // Если статус трекера не "completed", окрашиваем строку в красный
                    if (
                        $record->status == Time::STATUS_NEW ||
                        $record->status == Time::STATUS_CANCELED ||
                        $record->status == Time::STATUS_NEEDS_CLARIFICATION ||
                        $record->status == Time::STATUS_PLANNED ||
                        $record->status == Time::STATUS_IN_PROGRESS
                    ) {
                        $sheet->getStyle("A{$rowNumber}:{$highestColumn}{$rowNumber}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFB3B3'], // Светло-красный фон
                            ],
                        ]);
                    }

                    if ($record->status == Time::STATUS_EXPORT_AKT) {
                        $sheet->getStyle("A{$rowNumber}:{$highestColumn}{$rowNumber}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'B3FFB3'], // Светло-зеленый фон
                            ],
                        ]);
                    }
                    $rowNumber++;
                }

                // Добавляем легенду статусов
                $legendStartRow = $highestRow + 3;

                // Заголовок легенды
                $sheet->setCellValue("A{$legendStartRow}", 'ЛЕГЕНДА СТАТУСІВ ТРЕКЕРА:');
                $sheet->getStyle("A{$legendStartRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                ]);

                $legendRow = $legendStartRow + 1;

                // Статус: Виконано (белый фон)
                $sheet->setCellValue("A{$legendRow}", 'Виконано');
                $sheet->getStyle("A{$legendRow}:B{$legendRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFFFFF'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $legendRow++;

                // Статус: Новий, Відхилено, Потребує уточнення, Заплановано, В процесі (красный фон)
                $sheet->setCellValue("A{$legendRow}", 'Новий / Відхилено / Потребує уточнення / Заплановано / В процесі');
                $sheet->getStyle("A{$legendRow}:B{$legendRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFB3B3'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
                $legendRow++;

                // Статус: Експортовано в акти (зеленый фон)
                $sheet->setCellValue("A{$legendRow}", 'Експортовано в акти');
                $sheet->getStyle("A{$legendRow}:B{$legendRow}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'B3FFB3'],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Объединяем ячейки для каждой строки легенды
                $sheet->mergeCells("A{$legendStartRow}:B{$legendStartRow}");
                $sheet->mergeCells('A'.($legendStartRow + 1).':B'.($legendStartRow + 1));
                $sheet->mergeCells('A'.($legendStartRow + 2).':B'.($legendStartRow + 2));
                $sheet->mergeCells('A'.($legendStartRow + 3).':B'.($legendStartRow + 3));
            },
        ];
    }
}
