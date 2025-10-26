<?php

namespace App\Exports;

use App\Models\Time;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TimesExport implements FromCollection, WithColumnWidths, WithHeadings, WithStyles
{
    protected $query;

    protected $filename = 'Звіт_Times';

    protected $columns = [];

    public function __construct($query)
    {
        $this->query = $query;
        $this->setUp();
    }

    public function setUp(): void
    {
        $this->withFilename(date('Y-m-d').' - Звіт_Times');
        $this->withColumns([
            'id' => 'ID',
            'user.name' => 'Виконавець',
            'title' => 'Завдання',
            'duration' => 'Годин',
            'coefficient' => 'Коефіцієнт',
            'calculated_amount' => 'Сума, грн',
            'status' => 'Статус',
            'report_status' => 'Статус акту',
            'is_archived' => 'Архів',
            'created_at' => 'Створено',
            'updated_at' => 'Оновлено',
        ]);
    }

    public function withFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function withColumns(array $columns): self
    {
        $this->columns = $columns;

        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function collection()
    {
        return $this->query->get()->map(function ($row) {
            return [
                $row->id,
                $row->user->name ?? '',
                $row->title,
                number_format($row->duration / 3600, 2, '.', ''),
                $row->coefficient,
                number_format($row->duration / 3600 * $row->coefficient * Time::PRICE, 2, '.', ''),
                $row->status ? Time::$statuses[$row->status] : '',
                $row->report_status ? Time::$reportStatuses[$row->report_status] : '',
                $row->is_archived ? 'Так' : 'Ні',
                $row->created_at?->format('d.m.Y H:i'),
                $row->updated_at?->format('d.m.Y H:i'),
            ];
        });
    }

    public function headings(): array
    {
        return array_values($this->columns);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,  // ID
            'B' => 20,  // Виконавець
            'C' => 50,  // Завдання
            'D' => 12,  // Годин
            'E' => 12,  // Коефіцієнт
            'F' => 15,  // Сума
            'G' => 20,  // Статус
            'H' => 20,  // Статус акту
            'I' => 10,  // Архів
            'J' => 20,  // Створено
            'K' => 20,  // Оновлено
        ];
    }

    public function styles(Worksheet $sheet)
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
