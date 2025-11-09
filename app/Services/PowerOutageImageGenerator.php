<?php

namespace App\Services;

use App\Models\PowerOutageSchedule;
use Imagick;
use ImagickDraw;
use ImagickPixel;

class PowerOutageImageGenerator
{
    protected int $cellWidth = 100;  // Значно збільшено

    protected int $cellHeight = 50; // Значно збільшено висоту

    protected int $headerHeight = 180; // Більше місця для заголовків та легенди

    protected int $padding = 20;

    protected int $labelWidth = 90; // Ширина для підписів черг

    public function generate(PowerOutageSchedule $schedule): string
    {
        $data = $schedule->schedule_data;
        $groupedData = $this->groupByQueue($data);

        $hours = 24;
        $totalRows = count($data);

        $width = ($hours * $this->cellWidth) + $this->labelWidth + ($this->padding * 2) + 20;
        $height = ($totalRows * $this->cellHeight) + $this->headerHeight + ($this->padding * 2) + 650; // Більше місця для періодів

        // Створюємо зображення з вищою якістю
        $image = new Imagick;
        $image->newImage($width, $height, new ImagickPixel('white'));
        $image->setImageFormat('png');
        $image->setImageCompressionQuality(100);
        $image->setImageDepth(8);

        // Фон для заголовка вгорі
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#F5F5F5'));
        $draw->rectangle(0, 0, $width, 140);
        $image->drawImage($draw);

        // Заголовок та дата по центру вгорі
        $date = $schedule->schedule_date->format('d.m.Y');
        $time = $schedule->fetched_at->format('H:i');
        $centerX = $width / 2 - 180;

        $draw = new ImagickDraw;
        $this->drawText($draw, "Графік відключень - {$date}", $centerX, 35, 26, true);
        $image->drawImage($draw);

        $draw = new ImagickDraw;
        $this->drawText($draw, "Оновлено: {$time}", $centerX + 100, 65, 18);
        $image->drawImage($draw);

        // Легенда у верхньому блоці
        $legendY = 110;
        $legendX = $this->padding + 20;

        $draw = new ImagickDraw;
        $this->drawText($draw, 'Легенда:', $legendX, $legendY, 18, true);
        $image->drawImage($draw);

        $legendX += 100;

        // Зелений
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#66BB6A'));
        $draw->setStrokeColor(new ImagickPixel('#BDBDBD'));
        $draw->setStrokeWidth(1);
        $draw->rectangle($legendX, $legendY - 16, $legendX + 35, $legendY + 6);
        $image->drawImage($draw);

        $draw = new ImagickDraw;
        $this->drawText($draw, '- Світло є', $legendX + 40, $legendY, 17);
        $image->drawImage($draw);

        // Червоний
        $legendX += 160;
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#EF5350'));
        $draw->setStrokeColor(new ImagickPixel('#BDBDBD'));
        $draw->setStrokeWidth(1);
        $draw->rectangle($legendX, $legendY - 16, $legendX + 35, $legendY + 6);
        $image->drawImage($draw);

        $draw = new ImagickDraw;
        $this->drawText($draw, '- Вимкнено', $legendX + 40, $legendY, 17);
        $image->drawImage($draw);

        // Жовтий
        $legendX += 160;
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#FFC107'));
        $draw->setStrokeColor(new ImagickPixel('#BDBDBD'));
        $draw->setStrokeWidth(1);
        $draw->rectangle($legendX, $legendY - 16, $legendX + 35, $legendY + 6);
        $image->drawImage($draw);

        $draw = new ImagickDraw;
        $this->drawText($draw, '- Можливо', $legendX + 40, $legendY, 17);
        $image->drawImage($draw);

        // Пояснення символу ⚠️
        $legendX += 160;
        $draw = new ImagickDraw;
        $this->drawText($draw, '⚠️ - можливе відключення', $legendX, $legendY, 17);
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
            $draw->rectangle($x, $startY - 85, $x + $this->cellWidth, $startY);
            $image->drawImage($draw);

            // "з 00:00"
            $draw = new ImagickDraw;
            $fromText = sprintf('з %02d:00', $hour);
            $this->drawText($draw, $fromText, $x + 23, $startY - 60, 18, true);
            $image->drawImage($draw);

            // "по 01:00"
            $toHour = ($hour + 1) % 24;
            $draw = new ImagickDraw;
            $toText = sprintf('по %02d:00', $toHour);
            $this->drawText($draw, $toText, $x + 10, $startY - 43, 18, true);
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
            $this->drawText($draw, $hourText, $x + 35, $startY - 6, 28, true);
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
                $this->drawText($draw, $label, $this->padding + 32, $currentY + 32, 20, true);
                $image->drawImage($draw);

                // Малюємо клітинки для кожної години
                for ($hour = 0; $hour < $hours; $hour++) {
                    $x = $startX + ($hour * $this->cellWidth);

                    // Перші 30 хвилин (0-30)
                    $index1 = $hour * 2;
                    $status1 = $subqueueData['hourly_status'][$index1] ?? 'on';

                    // Другі 30 хвилин (30-60)
                    $index2 = $hour * 2 + 1;
                    $status2 = $subqueueData['hourly_status'][$index2] ?? 'on';

                    // Ліва половина (0-30 хв)
                    $color1 = match ($status1) {
                        'off' => '#EF5350',
                        'maybe' => '#FFC107',
                        'on' => '#66BB6A',
                        default => '#FFFFFF'
                    };

                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel($color1));
                    $draw->setStrokeColor(new ImagickPixel('#E0E0E0'));
                    $draw->setStrokeWidth(1);
                    $draw->rectangle($x, $currentY, $x + $this->cellWidth / 2, $currentY + $this->cellHeight);
                    $image->drawImage($draw);

                    // Права половина (30-60 хв)
                    $color2 = match ($status2) {
                        'off' => '#EF5350',
                        'maybe' => '#FFC107',
                        'on' => '#66BB6A',
                        default => '#FFFFFF'
                    };

                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel($color2));
                    $draw->setStrokeColor(new ImagickPixel('#E0E0E0'));
                    $draw->setStrokeWidth(1);
                    $draw->rectangle($x + $this->cellWidth / 2, $currentY, $x + $this->cellWidth, $currentY + $this->cellHeight);
                    $image->drawImage($draw);
                }

                $currentY += $this->cellHeight;
            }
        }

        // Додаємо інформацію про періоди відключень внизу
        $bottomY = $currentY + 30;

        // Заголовок секції
        $draw = new ImagickDraw;
        $this->drawText($draw, 'Періоди відключень:', $this->padding + 10, $bottomY, 20, true);
        $image->drawImage($draw);

        $bottomY += 35;
        $columnWidth = 330; // Ширина колонки для таблиці
        $currentX = $this->padding + 10;
        $currentY = $bottomY;
        $maxQueueHeight = 0;

        // Перегруповуємо дані: 1.1, 1.2 | 2.1, 2.2 | 3.1, 3.2 | ...
        foreach ($groupedData as $queueName => $subqueues) {
            $columnStartY = $currentY;

            foreach ($subqueues as $subqueueData) {
                $subqueue = $subqueueData['subqueue'];
                $label = "{$queueName}.{$subqueue}";
                $periods = $this->calculateOutagePeriods($subqueueData['hourly_status']);

                // Малюємо комірку з кольором черги
                $queueColors = [
                    '1' => '#FFD700', // Жовтий
                    '2' => '#7CFC00', // Зелений
                    '3' => '#FF8C00', // По��аранчевий
                    '4' => '#00BFFF', // Блакитний
                    '5' => '#FF69B4', // Рожевий
                    '6' => '#9370DB', // Фіолетовий
                ];
                $bgColor = $queueColors[$queueName] ?? '#DDDDDD';

                $cellStartY = $currentY;
                $cellHeight = 35; // Висота заголовка

                // Об'єднуємо всі періоди (або показуємо "Немає відключень")
                $allPeriods = array_merge($periods['off'], $periods['maybe']);

                if (empty($allPeriods)) {
                    $allPeriods = ['Немає відключень'];
                }

                $cellHeight += count($allPeriods) * 24; // Додаємо висоту для кожного періоду

                // Малюємо рамку комірки
                $draw = new ImagickDraw;
                $draw->setStrokeColor(new ImagickPixel('#999999'));
                $draw->setStrokeWidth(2);
                $draw->setFillColor(new ImagickPixel('white'));
                $draw->rectangle($currentX, $cellStartY, $currentX + $columnWidth - 5, $cellStartY + $cellHeight);
                $image->drawImage($draw);

                // Заголовок черги з кольоровим фоном
                $draw = new ImagickDraw;
                $draw->setFillColor(new ImagickPixel($bgColor));
                $draw->rectangle($currentX, $cellStartY, $currentX + $columnWidth - 5, $cellStartY + 30);
                $image->drawImage($draw);

                // Назва черги
                $draw = new ImagickDraw;
                $this->drawText($draw, "Черга {$label}", $currentX + 12, $cellStartY + 21, 18, true);
                $image->drawImage($draw);

                // Відображаємо періоди у стовпчик
                $lineY = $cellStartY + 50;

                foreach ($allPeriods as $period) {
                    $draw = new ImagickDraw;
                    $this->drawText($draw, $period, $currentX + 12, $lineY, 15);
                    $image->drawImage($draw);
                    $lineY += 24;
                }

                // Переходимо до наступної комірки в стовпчику
                $currentY += $cellHeight + 10;
            }

            // Запам'ятовуємо максимальну висоту колонки
            $columnHeight = $currentY - $columnStartY;
            if ($columnHeight > $maxQueueHeight) {
                $maxQueueHeight = $columnHeight;
            }

            // Переходимо до наступної колонки (наступної черги)
            $currentX += $columnWidth;
            $currentY = $columnStartY; // Повертаємося на початок для наступної колонки

            // Якщо досягли краю, переходимо на новий рядок
            if ($currentX + $columnWidth > $width - $this->padding) {
                $currentX = $this->padding + 10;
                $currentY = $columnStartY + $maxQueueHeight;
                $maxQueueHeight = 0;
            }
        }

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
        $seen = [];

        foreach ($data as $row) {
            //            "1 черга" - remove " черга"
            $queue = str_replace(' черга', '', $row['queue']);
            $subqueue = $row['subqueue'];

            // Створюємо унікальний ключ для перевірки дублікатів
            $uniqueKey = "{$queue}.{$subqueue}";

            // Пропускаємо дублікати
            if (isset($seen[$uniqueKey])) {
                continue;
            }

            $seen[$uniqueKey] = true;

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
        $draw->setTextAntialias(true); // Згладжування шрифту
        $draw->annotation($x, $y, $text);
    }

    /**
     * Обчислює періоди відключень для черги
     */
    protected function calculateOutagePeriods(array $hourlyStatus): array
    {
        $periods = ['off' => [], 'maybe' => []];
        $currentPeriod = null;
        $currentType = null;

        for ($i = 0; $i < 48; $i++) {
            $status = $hourlyStatus[$i] ?? 'on';

            if ($status === 'off' || $status === 'maybe') {
                if ($currentType === $status) {
                    // Продовжуємо поточний період
                    $currentPeriod['end'] = $i;
                } else {
                    // Зберігаємо попередній період
                    if ($currentPeriod !== null) {
                        $formattedPeriod = $this->formatPeriod($currentPeriod['start'], $currentPeriod['end']);
                        // Додаємо позначку для жовтих періодів
                        if ($currentType === 'maybe') {
                            $formattedPeriod .= ' ⚠️';
                        }
                        $periods[$currentType][] = [
                            'text' => $formattedPeriod,
                            'start' => $currentPeriod['start'],
                        ];
                    }
                    // Починаємо новий період
                    $currentPeriod = ['start' => $i, 'end' => $i];
                    $currentType = $status;
                }
            } else {
                // Статус 'on' - зберігаємо поточний період якщо є
                if ($currentPeriod !== null) {
                    $formattedPeriod = $this->formatPeriod($currentPeriod['start'], $currentPeriod['end']);
                    // Додаємо позначку для жовтих періодів
                    if ($currentType === 'maybe') {
                        $formattedPeriod .= ' ⚠️';
                    }
                    $periods[$currentType][] = [
                        'text' => $formattedPeriod,
                        'start' => $currentPeriod['start'],
                    ];
                    $currentPeriod = null;
                    $currentType = null;
                }
            }
        }

        // Зберігаємо останній період
        if ($currentPeriod !== null) {
            $formattedPeriod = $this->formatPeriod($currentPeriod['start'], $currentPeriod['end']);
            // Додаємо позначку для жовтих періодів
            if ($currentType === 'maybe') {
                $formattedPeriod .= ' ⚠️';
            }
            $periods[$currentType][] = [
                'text' => $formattedPeriod,
                'start' => $currentPeriod['start'],
            ];
        }

        // Об'єднуємо та сортуємо по часу початку
        $allPeriods = array_merge($periods['off'], $periods['maybe']);
        usort($allPeriods, fn ($a, $b) => $a['start'] <=> $b['start']);

        // Повертаємо тільки текст
        return [
            'off' => array_column($allPeriods, 'text'),
            'maybe' => [], // Порожній масив, бо всі періоди вже в off
        ];
    }

    /**
     * Форматує період з індексів в час
     */
    protected function formatPeriod(int $startIndex, int $endIndex): string
    {
        $startHour = intdiv($startIndex, 2);
        $startMin = ($startIndex % 2) * 30;
        $endHour = intdiv($endIndex + 1, 2);
        $endMin = (($endIndex + 1) % 2) * 30;

        // Якщо end = 24:00, показуємо як 00:00
        if ($endHour >= 24) {
            $endHour = 0;
            $endMin = 0;
        }

        return sprintf('%02d:%02d - %02d:%02d', $startHour, $startMin, $endHour, $endMin);
    }
}
