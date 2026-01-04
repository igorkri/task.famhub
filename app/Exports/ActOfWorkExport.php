<?php

namespace App\Exports;

use App\Models\ActOfWork;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActOfWorkExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        protected ActOfWork $actOfWork
    ) {}

    public function title(): string
    {
        return 'Акт '.$this->actOfWork->number;
    }

    public function collection()
    {
        return $this->actOfWork->details;
    }

    public function headings(): array
    {
        return [
            '№',
            'Проект',
            'Задача',
            'Опис',
            'Години',
            'Сума (грн)',
        ];
    }

    /**
     * @param  \App\Models\ActOfWorkDetail  $row
     */
    public function map($row): array
    {
        static $index = 0;
        $index++;

        return [
            $index,
            $row->project ?? '—',
            $row->task ?? '—',
            $row->description ?? '—',
            number_format((float) $row->hours, 2),
            number_format((float) $row->amount, 2),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        return [
            // Стиль заголовків
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
            ],

            // Рамки для всіх комірок
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
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Додаємо інформацію про акт
                $infoRow = $highestRow + 2;

                $sheet->setCellValue("A{$infoRow}", 'Інформація про акт:');
                $sheet->getStyle("A{$infoRow}")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12],
                ]);

                $infoRow++;
                $sheet->setCellValue("A{$infoRow}", 'Номер акту:');
                $sheet->setCellValue("B{$infoRow}", $this->actOfWork->number);

                $infoRow++;
                $sheet->setCellValue("A{$infoRow}", 'Дата:');
                $sheet->setCellValue("B{$infoRow}", $this->actOfWork->date?->format('d.m.Y') ?? '—');

                $infoRow++;
                $sheet->setCellValue("A{$infoRow}", 'Період:');
                $sheet->setCellValue("B{$infoRow}", $this->actOfWork->getPeriodText());

                $infoRow++;
                $sheet->setCellValue("A{$infoRow}", 'Загальна сума:');
                $sheet->setCellValue("B{$infoRow}", number_format((float) $this->actOfWork->total_amount, 2).' грн');
                $sheet->getStyle("B{$infoRow}")->applyFromArray([
                    'font' => ['bold' => true],
                ]);

                $infoRow++;
                $sheet->setCellValue("A{$infoRow}", 'Оплачено:');
                $sheet->setCellValue("B{$infoRow}", number_format((float) $this->actOfWork->paid_amount, 2).' грн');

                // Встановлюємо ширину колонок
                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(25);
                $sheet->getColumnDimension('C')->setWidth(40);
                $sheet->getColumnDimension('D')->setWidth(50);
                $sheet->getColumnDimension('E')->setWidth(12);
                $sheet->getColumnDimension('F')->setWidth(15);
            },
        ];
    }
}
