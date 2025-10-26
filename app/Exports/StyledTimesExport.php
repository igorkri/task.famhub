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
            Column::make('id')->heading('ID'),
            Column::make('user.name')->heading('Виконавець'),
            Column::make('title')->heading('Завдання'),
            Column::make('duration')
                ->heading('Годин')
                ->formatStateUsing(fn ($state) => number_format($state / 3600, 2, '.', '')),
            Column::make('coefficient')->heading('Коефіцієнт'),
            Column::make('calculated_amount')
                ->heading('Сума, грн')
                ->formatStateUsing(fn ($state, $record) => number_format(
                    $record->duration / 3600 * $record->coefficient * Time::PRICE,
                    2,
                    '.',
                    ''
                )),
            Column::make('status')
                ->heading('Статус')
                ->formatStateUsing(fn ($state) => $state ? Time::$statuses[$state] : ''),
            Column::make('report_status')
                ->heading('Статус акту')
                ->formatStateUsing(fn ($state) => $state ? Time::$reportStatuses[$state] : ''),
            Column::make('is_archived')
                ->heading('Архів')
                ->formatStateUsing(fn ($state) => $state ? 'Так' : 'Ні'),
            Column::make('created_at')
                ->heading('Створено')
                ->formatStateUsing(fn ($state) => $state?->format('d.m.Y H:i')),
            Column::make('updated_at')
                ->heading('Оновлено')
                ->formatStateUsing(fn ($state) => $state?->format('d.m.Y H:i')),
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

            // Вирівнювання числових колонок по центру
            "D2:D{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            "E2:E{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            "F2:F{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
            ],
        ];
    }
}
