<?php

namespace App\Exports;

use App\Models\Time;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class StyledTimesExport extends ExcelExport implements WithStyles
{
    public function setUp(): void
    {
        $this->withFilename(fn () => date('Y-m-d').' - Звіт_Times');
        $this->withColumns([
            Column::make('title')->heading('Назва задачі'),
            Column::make('task.project.name')->heading('Проект'),
            Column::make('duration')
                ->heading('Час')
                ->formatStateUsing(fn ($state) => number_format($state / 3600, 2, '.', '')),
            Column::make('coefficient')->heading('Коефіцієнт'),
            Column::make('calculated_amount')
                ->heading('Ціна')
                ->formatStateUsing(fn ($state, $record) => number_format(
                    $record->duration / 3600 * $record->coefficient * Time::PRICE,
                    2,
                    '.',
                    ''
                )),
            Column::make('comment')->heading('Коментар'),
            Column::make('updated_at')
                ->heading('Дата модифікації таксу')
                ->formatStateUsing(fn ($state) => $state?->format('d.m.Y H:i')),
            Column::make('task.permalink_url')
                ->heading('Посилання на задачу')
                ->formatStateUsing(fn ($state, $record) => $record->task?->permalink_url ? '=HYPERLINK("'.$record->task->permalink_url.'", "Відкрити задачу")' : ''),
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
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '000000'],
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
                    'color' => ['rgb' => '0563C1'],
                    'underline' => true,
                ],
            ],
        ];
    }
}
