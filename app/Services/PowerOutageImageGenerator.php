<?php

namespace App\Services;

use App\Models\PowerOutageSchedule;
use Imagick;
use ImagickDraw;
use ImagickPixel;

class PowerOutageImageGenerator
{
    protected int $cellWidth = 100;

    protected int $cellHeight = 50;

    protected int $headerHeight = 120;

    protected int $padding = 25;

    protected int $labelWidth = 100;

    public function generate(PowerOutageSchedule $schedule): string
    {
        $data = $schedule->schedule_data;
        $groupedData = $this->groupByQueue($data);

        $hours = 24;
        $totalRows = count($data);

        $width = ($hours * $this->cellWidth) + $this->labelWidth + ($this->padding * 2) + 20;
        $height = ($totalRows * $this->cellHeight) + $this->headerHeight + ($this->padding * 2) + 750;

        // Створюємо зображення з вищою якістю
        $image = new Imagick;
        $image->newImage($width, $height, new ImagickPixel('#F8F9FA'));
        $image->setImageFormat('png');
        $image->setImageCompressionQuality(100);
        $image->setImageDepth(8);

        // Градієнтний фон для заголовка
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#1E3A8A'));
        $draw->rectangle(0, 0, $width, 100);
        $image->drawImage($draw);
        
        // Декоративна смуга
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#3B82F6'));
        $draw->rectangle(0, 100, $width, 110);
        $image->drawImage($draw);

        // Заголовок та дата по центру вгорі
        $date = $schedule->schedule_date->format('d.m.Y');
        $time = $schedule->fetched_at->format('H:i');
        $centerX = $width / 2 - 320;

        // Іконка блискавки
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#FCD34D'));
        $draw->setFont('DejaVu-Sans');
        $draw->setFontSize(40);
        $draw->annotation($centerX - 50, 58, '⚡');
        $image->drawImage($draw);

        // Заголовок білим кольором
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#FFFFFF'));
        $draw->setFont('DejaVu-Sans-Bold');
        $draw->setFontSize(28);
        $draw->setTextAntialias(true);
        $draw->annotation($centerX + 20, 50, "Графік відключень електроенергії");
        $image->drawImage($draw);

        // Дата та час оновлення (з меншим відступом)
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#FCD34D'));
        $draw->setFont('DejaVu-Sans-Bold');
        $draw->setFontSize(20);
        $draw->annotation($centerX + 100, 75, "📅 {$date}  •  🕐 Оновлено: {$time}");
        $image->drawImage($draw);

        $startX = $this->padding + $this->labelWidth;
        $startY = $this->headerHeight + 60;

        // Малюємо заголовки часу з градієнтом
        for ($hour = 0; $hour < $hours; $hour++) {
            $x = $startX + ($hour * $this->cellWidth);

            // Градієнтний фон для заголовка часу
            $draw = new ImagickDraw;
            $draw->setStrokeColor(new ImagickPixel('#CBD5E1'));
            $draw->setStrokeWidth(1);
            
            // Чергуємо кольори для кращої читабельності
            $bgColor = ($hour % 2 === 0) ? '#F1F5F9' : '#E2E8F0';
            $draw->setFillColor(new ImagickPixel($bgColor));
            $draw->rectangle($x, $startY - 100, $x + $this->cellWidth, $startY);
            $image->drawImage($draw);

            // "з 00:00" - менший текст
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#64748B'));
            $draw->setFont('DejaVu-Sans');
            $draw->setFontSize(14);
            $draw->setTextAntialias(true);
            $fromText = sprintf('з %02d:00', $hour);
            $draw->annotation($x + 18, $startY - 68, $fromText);
            $image->drawImage($draw);

            // "по 01:00" - менший текст
            $toHour = ($hour + 1) % 24;
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#64748B'));
            $draw->setFont('DejaVu-Sans');
            $draw->setFontSize(14);
            $toText = sprintf('по %02d:00', $toHour);
            $draw->annotation($x + 10, $startY - 48, $toText);
            $image->drawImage($draw);
            
            // Велика година по центру
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#1E293B'));
            $draw->setFont('DejaVu-Sans-Bold');
            $draw->setFontSize(26);
            $hourText = sprintf('%02d', $hour);
            $draw->annotation($x + 32, $startY - 15, $hourText);
            $image->drawImage($draw);
        }

        // Малюємо дані по чергах
        $currentY = $startY;
        $queueStats = []; // Для збору статистики

        foreach ($groupedData as $queueName => $subqueues) {
            foreach ($subqueues as $subqueueData) {
                $subqueue = $subqueueData['subqueue'];
                
                // Підраховуємо статистику
                $offCount = count(array_filter($subqueueData['hourly_status'], fn($s) => $s === 'off'));
                $totalHours = round($offCount * 0.5, 1); // кожен сегмент = 30 хв
                $queueStats["{$queueName}.{$subqueue}"] = $totalHours;
                
                // Підпис черги з кольоровою заливкою (як у заголовках карточок)
                $draw = new ImagickDraw;
                $draw->setStrokeColor(new ImagickPixel('#94A3B8'));
                $draw->setStrokeWidth(1.5);
                
                // Використовуємо ті ж кольори що й у карточках
                $queueColors = [
                    '1' => '#FFD700', // Жовтий
                    '2' => '#7CFC00', // Зелений
                    '3' => '#FF8C00', // Помаранчевий
                    '4' => '#00BFFF', // Блакитний
                    '5' => '#FF69B4', // Рожевий
                    '6' => '#9370DB', // Фіолетовий
                ];
                $bgColor = $queueColors[$queueName] ?? '#F3F4F6';
                $draw->setFillColor(new ImagickPixel($bgColor));
                $draw->rectangle($this->padding, $currentY, $this->padding + $this->labelWidth, $currentY + $this->cellHeight);
                $image->drawImage($draw);

                // Номер черги великим шрифтом
                $draw = new ImagickDraw;
                $draw->setFillColor(new ImagickPixel('#1F2937'));
                $draw->setFont('DejaVu-Sans-Bold');
                $draw->setFontSize(22);
                $label = "{$queueName}.{$subqueue}";
                $draw->annotation($this->padding + 28, $currentY + 34, $label);
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

                    // Ліва половина (0-30 хв) з покращеними кольорами
                    $color1 = match ($status1) {
                        'off' => '#DC2626',    // Яскравіший червоний
                        'maybe' => '#F59E0B',  // Яскравіший жовтий
                        'on' => '#10B981',     // Яскравіший зелений
                        default => '#FFFFFF'
                    };

                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel($color1));
                    $draw->setStrokeColor(new ImagickPixel('#D1D5DB'));
                    $draw->setStrokeWidth(0.5);
                    $draw->rectangle($x, $currentY, $x + $this->cellWidth / 2, $currentY + $this->cellHeight);
                    $image->drawImage($draw);

                    // Права половина (30-60 хв)
                    $color2 = match ($status2) {
                        'off' => '#DC2626',
                        'maybe' => '#F59E0B',
                        'on' => '#10B981',
                        default => '#FFFFFF'
                    };

                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel($color2));
                    $draw->setStrokeColor(new ImagickPixel('#D1D5DB'));
                    $draw->setStrokeWidth(0.5);
                    $draw->rectangle($x + $this->cellWidth / 2, $currentY, $x + $this->cellWidth, $currentY + $this->cellHeight);
                    $image->drawImage($draw);
                    
                    // Додаємо іконки для важливих періодів
                    if ($status1 === 'off' && $status2 === 'off') {
                        // Обидві половини червоні - додаємо іконку
                        $draw = new ImagickDraw;
                        $draw->setFillColor(new ImagickPixel('#FFFFFF'));
                        $draw->setFont('DejaVu-Sans');
                        $draw->setFontSize(16);
                        $draw->annotation($x + 38, $currentY + 33, '⚠️');
                        $image->drawImage($draw);
                    }
                }

                $currentY += $this->cellHeight;
            }
        }

        // Додаємо інформацію про періоди відключень внизу
        $bottomY = $currentY + 40;

        // Красива легенда з рамкою
        $legendY = $bottomY;
        $legendX = $this->padding + 10;
        
        // Фон для легенди
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#FFFFFF'));
        $draw->setStrokeColor(new ImagickPixel('#E5E7EB'));
        $draw->setStrokeWidth(2);
        $draw->rectangle($legendX - 5, $legendY - 25, $width - $this->padding - 5, $legendY + 30);
        $image->drawImage($draw);

        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#1F2937'));
        $draw->setFont('DejaVu-Sans-Bold');
        $draw->setFontSize(18);
        $draw->annotation($legendX + 5, $legendY - 5, '📊 Легенда:');
        $image->drawImage($draw);

        $legendX += 130;

        // Зелений - з тінню
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#10B981'));
        $draw->setStrokeColor(new ImagickPixel('#059669'));
        $draw->setStrokeWidth(2);
        $draw->roundRectangle($legendX, $legendY - 18, $legendX + 40, $legendY + 8, 4, 4);
        $image->drawImage($draw);

        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#1F2937'));
        $draw->setFont('DejaVu-Sans');
        $draw->setFontSize(16);
        $draw->annotation($legendX + 48, $legendY + 2, '✓ Світло є');
        $image->drawImage($draw);

        // Червоний
        $legendX += 180;
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#DC2626'));
        $draw->setStrokeColor(new ImagickPixel('#B91C1C'));
        $draw->setStrokeWidth(2);
        $draw->roundRectangle($legendX, $legendY - 18, $legendX + 40, $legendY + 8, 4, 4);
        $image->drawImage($draw);

        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#1F2937'));
        $draw->setFont('DejaVu-Sans');
        $draw->setFontSize(16);
        $draw->annotation($legendX + 48, $legendY + 2, '✗ Вимкнено');
        $image->drawImage($draw);

        // Жовтий
        $legendX += 200;
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#F59E0B'));
        $draw->setStrokeColor(new ImagickPixel('#D97706'));
        $draw->setStrokeWidth(2);
        $draw->roundRectangle($legendX, $legendY - 18, $legendX + 40, $legendY + 8, 4, 4);
        $image->drawImage($draw);

        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#1F2937'));
        $draw->setFont('DejaVu-Sans');
        $draw->setFontSize(16);
        $draw->annotation($legendX + 48, $legendY + 2, '⚠️ Можливо');
        $image->drawImage($draw);

        // Пояснення
        $legendX += 200;
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#6B7280'));
        $draw->setFont('DejaVu-Sans');
        $draw->setFontSize(14);
        $draw->annotation($legendX, $legendY + 2, '(кожна клітинка = 30 хв)');
        $image->drawImage($draw);

        $bottomY += 65;

        // Заголовок секції
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#1F2937'));
        $draw->setFont('DejaVu-Sans-Bold');
        $draw->setFontSize(20);
        $draw->annotation($this->padding + 10, $bottomY, '🕐 Детальні періоди відключень:');
        $image->drawImage($draw);

        $bottomY += 40;
        $columnWidth = 330; // Ширина колонки для таблиці
        $currentX = $this->padding + 10;
        $currentY = $bottomY;
        $maxQueueHeight = 0;
        
        // Залишаємо місце для статистики справа
        $maxCardsWidth = $width - 500; // Резервуємо 500px для статистики справа

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

                // Малюємо рамку комірки з тінню
                $draw = new ImagickDraw;
                $draw->setStrokeColor(new ImagickPixel('#9CA3AF'));
                $draw->setStrokeWidth(2);
                $draw->setFillColor(new ImagickPixel('#FFFFFF'));
                
                // Додаємо тінь
                $shadowDraw = new ImagickDraw;
                $shadowDraw->setFillColor(new ImagickPixel('#00000020'));
                $shadowDraw->roundRectangle(
                    $currentX + 3, 
                    $cellStartY + 3, 
                    $currentX + $columnWidth - 2, 
                    $cellStartY + $cellHeight + 3,
                    8, 8
                );
                $image->drawImage($shadowDraw);
                
                // Основна рамка
                $draw->roundRectangle(
                    $currentX, 
                    $cellStartY, 
                    $currentX + $columnWidth - 5, 
                    $cellStartY + $cellHeight,
                    8, 8
                );
                $image->drawImage($draw);

                // Заголовок черги з градієнтним фоном
                $draw = new ImagickDraw;
                $draw->setFillColor(new ImagickPixel($bgColor));
                $draw->setStrokeColor(new ImagickPixel('#9CA3AF'));
                $draw->setStrokeWidth(1);
                $draw->roundRectangle(
                    $currentX + 2, 
                    $cellStartY + 2, 
                    $currentX + $columnWidth - 7, 
                    $cellStartY + 35,
                    6, 6
                );
                $image->drawImage($draw);

                // Назва черги з іконкою
                $draw = new ImagickDraw;
                $draw->setFillColor(new ImagickPixel('#1F2937'));
                $draw->setFont('DejaVu-Sans-Bold');
                $draw->setFontSize(18);
                $draw->annotation($currentX + 15, $cellStartY + 24, "⚡ Черга {$label}");
                $image->drawImage($draw);

                // Відображаємо періоди у стовпчик з іконками
                $lineY = $cellStartY + 55;

                foreach ($allPeriods as $period) {
                    // Визначаємо іконку залежно від наявності ⚠️
                    $icon = str_contains($period, '⚠️') ? '⚠️' : '🔴';
                    $textColor = str_contains($period, '⚠️') ? '#F59E0B' : '#DC2626';
                    
                    // Іконка
                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel('#1F2937'));
                    $draw->setFont('DejaVu-Sans');
                    $draw->setFontSize(14);
                    $draw->annotation($currentX + 15, $lineY, $icon);
                    $image->drawImage($draw);
                    
                    // Текст періоду
                    $periodText = str_replace(' ⚠️', '', $period);
                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel($textColor));
                    $draw->setFont('DejaVu-Sans');
                    $draw->setFontSize(15);
                    $draw->annotation($currentX + 35, $lineY, $periodText);
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

            // Якщо досягли краю (з урахуванням місця для статистики), переходимо на новий рядок
            if ($currentX + $columnWidth > $maxCardsWidth) {
                $currentX = $this->padding + 10;
                $currentY = $columnStartY + $maxQueueHeight;
                $maxQueueHeight = 0;
            }
        }
        
        // Малюємо статистичну панель справа від карток
        if (!empty($queueStats)) {
            // Позиція статистики - фіксована праворуч
            $statsX = $maxCardsWidth + 20; // 20px відступ від карток
            $statsY = $bottomY;
            
            // Фон для статистики
            $statsHeight = count(array_filter($queueStats, fn($h) => $h > 0)) * 35 + 70;
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#FFFFFF'));
            $draw->setStrokeColor(new ImagickPixel('#9CA3AF'));
            $draw->setStrokeWidth(2);
            
            // Тінь
            $shadowDraw = new ImagickDraw;
            $shadowDraw->setFillColor(new ImagickPixel('#00000020'));
            $shadowDraw->roundRectangle($statsX + 3, $statsY + 3, $width - $this->padding - 7, $statsY + $statsHeight + 3, 8, 8);
            $image->drawImage($shadowDraw);
            
            // Основна рамка
            $draw->roundRectangle($statsX, $statsY, $width - $this->padding - 10, $statsY + $statsHeight, 8, 8);
            $image->drawImage($draw);
            
            // Заголовок з градієнтом
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#EEF2FF'));
            $draw->roundRectangle($statsX + 2, $statsY + 2, $width - $this->padding - 12, $statsY + 40, 6, 6);
            $image->drawImage($draw);
            
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#1F2937'));
            $draw->setFont('DejaVu-Sans-Bold');
            $draw->setFontSize(18);
            $draw->annotation($statsX + 15, $statsY + 28, '📈 Статистика відключень');
            $image->drawImage($draw);
            
            $statsY += 55;
            
            foreach ($queueStats as $queue => $hours) {
                if ($hours > 0) {
                    $percentage = round(($hours / 24) * 100);
                    
                    // Прогрес-бар (зменшена ширина)
                    $barWidth = 200;
                    
                    // Фон прогрес-бару
                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel('#E5E7EB'));
                    $draw->setStrokeColor(new ImagickPixel('#D1D5DB'));
                    $draw->setStrokeWidth(1);
                    $draw->roundRectangle($statsX + 90, $statsY - 15, $statsX + 90 + $barWidth, $statsY + 5, 3, 3);
                    $image->drawImage($draw);
                    
                    // Заповнення
                    $fillWidth = ($barWidth * $percentage) / 100;
                    $barColor = $percentage > 50 ? '#DC2626' : ($percentage > 25 ? '#F59E0B' : '#10B981');
                    
                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel($barColor));
                    $draw->roundRectangle($statsX + 90, $statsY - 15, $statsX + 90 + $fillWidth, $statsY + 5, 3, 3);
                    $image->drawImage($draw);
                    
                    // Текст черги
                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel('#374151'));
                    $draw->setFont('DejaVu-Sans');
                    $draw->setFontSize(15);
                    $draw->annotation($statsX + 15, $statsY, "Черга {$queue}:");
                    $image->drawImage($draw);
                    
                    // Значення
                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel('#1F2937'));
                    $draw->setFont('DejaVu-Sans-Bold');
                    $draw->setFontSize(14);
                    $draw->annotation($statsX + 300, $statsY, "{$hours}г ({$percentage}%)");
                    $image->drawImage($draw);
                    
                    $statsY += 32;
                }
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
