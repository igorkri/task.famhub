<?php

namespace App\Services;

use App\Models\PowerOutageSchedule;
use Imagick;
use ImagickDraw;
use ImagickPixel;

class PowerOutageImageGenerator
{
    protected int $cellWidth = 60;  // Збільшено для кращої якості

    protected int $cellHeight = 35; // Збільшено висоту

    protected int $headerHeight = 90; // Більше місця для заголовків

    protected int $padding = 15;

    protected int $labelWidth = 70; // Ширина для підписів черг

    public function generate(PowerOutageSchedule $schedule): string
    {
        $data = $schedule->schedule_data;
        $groupedData = $this->groupByQueue($data);

        $hours = 24;
        $totalRows = count($data);

        $width = ($hours * $this->cellWidth) + $this->labelWidth + ($this->padding * 2) + 20;
        $height = ($totalRows * $this->cellHeight) + $this->headerHeight + ($this->padding * 2) + 100; // більше місця

        // Створюємо зображення з вищою якістю
        $image = new Imagick;
        $image->newImage($width, $height, new ImagickPixel('white'));
        $image->setImageFormat('png');
        $image->setImageCompressionQuality(95);

        // Фон для заголовка вгорі
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#F5F5F5'));
        $draw->rectangle(0, 0, $width, 65);
        $image->drawImage($draw);

        // Заголовок та дата по центру вгорі
        $date = $schedule->schedule_date->format('d.m.Y');
        $time = $schedule->fetched_at->format('H:i');
        $centerX = $width / 2 - 130;

        $draw = new ImagickDraw;
        $this->drawText($draw, "Графік відключень - {$date}", $centerX, 25, 16, true);
        $image->drawImage($draw);

        $draw = new ImagickDraw;
        $this->drawText($draw, "Оновлено: {$time}", $centerX + 50, 50, 13);
        $image->drawImage($draw);

        $startX = $this->padding + $this->labelWidth;
        $startY = $this->headerHeight + 20; // відступ після заголовка

        // Малюємо заголовки часу з рамками
        for ($hour = 0; $hour < $hours; $hour++) {
            $x = $startX + ($hour * $this->cellWidth);

            // РАМКА навколо заголовка часу (як на скріншоті!)
            $draw = new ImagickDraw;
            $draw->setStrokeColor(new ImagickPixel('#999999'));
            $draw->setStrokeWidth(1);
            $draw->setFillColor(new ImagickPixel('#FAFAFA'));
            $draw->rectangle($x, $startY - 65, $x + $this->cellWidth, $startY);
            $image->drawImage($draw);

            // "з 00:00"
            $draw = new ImagickDraw;
            $fromText = sprintf('з %02d:00', $hour);
            $this->drawText($draw, $fromText, $x + 13, $startY - 50, 11); // було 9
            $image->drawImage($draw);

            // "по 01:00"
            $toHour = ($hour + 1) % 24;
            $draw = new ImagickDraw;
            $toText = sprintf('по %02d:00', $toHour);
            $this->drawText($draw, $toText, $x + 10, $startY - 35, 11); // було 9
            $image->drawImage($draw);

            // Горизонтальна лінія між "по" та годиною
            $draw = new ImagickDraw;
            $draw->setStrokeColor(new ImagickPixel('#DDDDDD'));
            $draw->setStrokeWidth(1);
            $draw->line($x + 3, $startY - 27, $x + $this->cellWidth - 3, $startY - 27);
            $image->drawImage($draw);

            // Година великим жирним шрифтом (по центру)
            $draw = new ImagickDraw;
            $hourText = sprintf('%02d', $hour);
            $this->drawText($draw, $hourText, $x + 21, $startY - 6, 18, true); // було 15
            $image->drawImage($draw);
        }

        // Малюємо дані по чергах
        $currentY = $startY;

        foreach ($groupedData as $queueName => $subqueues) {
            foreach ($subqueues as $subqueueData) {
                $subqueue = $subqueueData['subqueue'];
                // Підпис черги
                $draw = new ImagickDraw;
                $draw->setStrokeColor(new ImagickPixel('#CCCCCC'));
                $draw->setStrokeWidth(1);
                $draw->setFillColor(new ImagickPixel('#FAFAFA'));
                $draw->rectangle($this->padding, $currentY, $this->padding + $this->labelWidth, $currentY + $this->cellHeight);
                $image->drawImage($draw);

                // Відображаємо у форматі "1.1", "2.2" і т.д.
                $draw = new ImagickDraw;
                $label = "{$queueName}.{$subqueue}";
                $this->drawText($draw, $label, $this->padding + 25, $currentY + 23, 14, true);
                $image->drawImage($draw);

                // Малюємо клітинки для кожної години
                for ($hour = 0; $hour < $hours; $hour++) {
                    $x = $startX + ($hour * $this->cellWidth);

                    $index = $hour * 2;
                    $status = $subqueueData['hourly_status'][$index] ?? 'on';

                    $color = match ($status) {
                        'off' => '#EF5350',
                        'maybe' => '#FFC107',
                        'on' => '#66BB6A',
                        default => '#FFFFFF'
                    };

                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel($color));
                    $draw->setStrokeColor(new ImagickPixel('#E0E0E0'));
                    $draw->setStrokeWidth(1);
                    $draw->rectangle($x, $currentY, $x + $this->cellWidth, $currentY + $this->cellHeight);
                    $image->drawImage($draw);
                }

                $currentY += $this->cellHeight;
            }
        }

        // Легенда
        $legendY = $currentY + 22;

        $draw = new ImagickDraw;
        $this->drawText($draw, 'Легенда:', $this->padding + 12, $legendY, 12, true);
        $image->drawImage($draw);

        $legendX = $this->padding + 90;

        // Зелений
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#66BB6A'));
        $draw->setStrokeColor(new ImagickPixel('#BDBDBD'));
        $draw->setStrokeWidth(1);
        $draw->rectangle($legendX, $legendY - 12, $legendX + 25, $legendY + 5);
        $image->drawImage($draw);

        $draw = new ImagickDraw;
        $this->drawText($draw, '- Світло є', $legendX + 30, $legendY, 11);
        $image->drawImage($draw);

        // Червоний
        $legendX += 130;
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#EF5350'));
        $draw->setStrokeColor(new ImagickPixel('#BDBDBD'));
        $draw->setStrokeWidth(1);
        $draw->rectangle($legendX, $legendY - 12, $legendX + 25, $legendY + 5);
        $image->drawImage($draw);

        $draw = new ImagickDraw;
        $this->drawText($draw, '- Вимкнено', $legendX + 30, $legendY, 11);
        $image->drawImage($draw);

        // Жовтий
        $legendX += 130;
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#FFC107'));
        $draw->setStrokeColor(new ImagickPixel('#BDBDBD'));
        $draw->setStrokeWidth(1);
        $draw->rectangle($legendX, $legendY - 12, $legendX + 25, $legendY + 5);
        $image->drawImage($draw);

        $draw = new ImagickDraw;
        $this->drawText($draw, '- Можливо', $legendX + 30, $legendY, 11);
        $image->drawImage($draw);

        // Зберігаємо з високою якістю
        $filename = storage_path('app/temp/power_outage_'.uniqid().'.png');

        if (! file_exists(dirname($filename))) {
            mkdir(dirname($filename), 0755, true);
        }

        $image->writeImage($filename);
        $image->clear();
        $image->destroy();

        return $filename;
    }

    protected function groupByQueue(array $data): array
    {
        $grouped = [];

        foreach ($data as $row) {
//            "1 черга" - remove " черга"
            $queue = str_replace(' черга', '', $row['queue']);
            if (! isset($grouped[$queue])) {
                $grouped[$queue] = [];
            }
            $grouped[$queue][] = $row;
        }

        return $grouped;
    }

    protected function drawText(ImagickDraw $draw, string $text, int $x, int $y, int $size = 12, bool $bold = false): void
    {
        $draw->setFillColor(new ImagickPixel('black'));
        $draw->setFont('DejaVu-Sans'.($bold ? '-Bold' : ''));
        $draw->setFontSize($size);
        $draw->annotation($x, $y, $text);
    }
}
