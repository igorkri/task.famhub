<?php

namespace App\Exports;

use App\Models\Task;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class TasksExport extends ExcelExport implements WithEvents, WithStyles
{
    public function setUp(): void
    {
        $this->withFilename(fn () => date('Y-m-d').' - Звіт_Завдання');
        $this->withColumns([
            Column::make('project.name')->heading('Проект'),
            Column::make('title')->heading('Назва'),
            Column::make('user.name')->heading('Відповідальний'),
            Column::make('is_completed')
                ->heading('Завершено')
                ->formatStateUsing(fn ($state) => $state ? 'Так' : 'Ні'),
            Column::make('status')
                ->heading('Статус')
                ->formatStateUsing(fn ($state) => Task::$statuses[$state] ?? '-'),
            Column::make('priority')
                ->heading('Пріоритет')
                ->formatStateUsing(fn ($state) => Task::$priorities[$state] ?? '-'),
            Column::make('deadline')
                ->heading('Дедлайн')
                ->formatStateUsing(fn ($state) => $state?->format('d.m.Y') ?? '-'),
            Column::make('budget')
                ->heading('Бюджет')
                ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, '.', '') : '-'),
            Column::make('spent')
                ->heading('Витрачено')
                ->formatStateUsing(fn ($state) => $state ? number_format($state, 2, '.', '') : '-'),
            Column::make('progress')
                ->heading('Прогрес (%)')
                ->formatStateUsing(fn ($state) => $state ?? '-'),
            Column::make('start_date')
                ->heading('Початок')
                ->formatStateUsing(fn ($state) => $state?->format('d.m.Y H:i') ?? '-'),
            Column::make('end_date')
                ->heading('Завершення')
                ->formatStateUsing(fn ($state) => $state?->format('d.m.Y H:i') ?? '-'),
            Column::make('description')
                ->heading('Опис'),
            Column::make('permalink_url')
                ->heading('Посилання')
                ->formatStateUsing(fn ($state, $record) => $state ? '=HYPERLINK("'.$state.'", "'.$record->gid.'")' : ''),
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
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8F4F8'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
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

            // Вирівнювання числових колонок
            // D - Завершено (по центру)
            "D2:D{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            // E - Статус (по центру)
            "E2:E{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            // F - Пріоритет (по центру)
            "F2:F{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            // G - Дедлайн (по центру)
            "G2:G{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            // H - Бюджет (праворуч)
            "H2:H{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
            ],
            // I - Витрачено (праворуч)
            "I2:I{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT,
                ],
            ],
            // J - Прогрес (по центру)
            "J2:J{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ],
            // N - Посилання (по центру)
            "N2:N{$highestRow}" => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
                'font' => [
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

                // Получаем записи из базы данных
                $query = $this->getQuery();

                if (! $query) {
                    return;
                }

                $records = $query->get();

                // Начинаем со второй строки (первая - заголовки)
                $rowNumber = 2;

                foreach ($records as $record) {
                    // Цветовое кодирование статусов
                    $fillColor = null;

                    switch ($record->status) {
                        case Task::STATUS_NEW:
                            $fillColor = 'E3F2FD'; // Светло-голубой
                            break;
                        case Task::STATUS_IN_PROGRESS:
                            $fillColor = 'FFF9C4'; // Светло-желтый
                            break;
                        case Task::STATUS_COMPLETED:
                            $fillColor = 'C8E6C9'; // Светло-зеленый
                            break;
                        case Task::STATUS_CANCELED:
                            $fillColor = 'FFCDD2'; // Светло-красный
                            break;
                        case Task::STATUS_NEEDS_CLARIFICATION:
                            $fillColor = 'FFE0B2'; // Светло-оранжевый
                            break;
                    }

                    if ($fillColor) {
                        $sheet->getStyle("A{$rowNumber}:{$highestColumn}{$rowNumber}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $fillColor],
                            ],
                        ]);
                    }

                    $rowNumber++;
                }

                // Автоширина колонок
                foreach (range('A', $highestColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Добавляем легенду статусов
                $legendStartRow = $highestRow + 3;

                // Заголовок легенды
                $sheet->setCellValue("A{$legendStartRow}", 'ЛЕГЕНДА СТАТУСІВ:');
                $sheet->getStyle("A{$legendStartRow}")->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                    ],
                ]);

                $legendRow = $legendStartRow + 1;
                $legends = [
                    ['Новий', 'E3F2FD'],
                    ['В процесі', 'FFF9C4'],
                    ['Виконано', 'C8E6C9'],
                    ['Відхилено', 'FFCDD2'],
                    ['Потребує уточнення', 'FFE0B2'],
                ];

                foreach ($legends as $legend) {
                    $sheet->setCellValue("A{$legendRow}", $legend[0]);
                    $sheet->getStyle("A{$legendRow}:B{$legendRow}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $legend[1]],
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                    ]);
                    $legendRow++;
                }
            },
        ];
    }
}

