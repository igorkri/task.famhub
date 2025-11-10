<?php

namespace App\Services;

use App\Models\PowerOutageSchedule;
use Imagick;
use ImagickDraw;
use ImagickPixel;

class PowerOutageImageGenerator
{
    protected int $cellWidth = 30; // Вужчі клітинки для компактності

    protected int $cellHeight = 28; // Ще менше

    protected int $headerHeight = 70; // Ще менше

    protected int $padding = 10; // Ще менше

    protected int $labelWidth = 45; // Ще менше

    public function generate(PowerOutageSchedule $schedule): string
    {
        $data = $schedule->schedule_data;
        $groupedData = $this->groupByQueue($data);

        $hours = 24;
        $totalRows = count($data);

        // Розраховуємо динамічну висоту для карточок
        $cardsHeight = $this->calculateCardsHeight($groupedData);

        $width = ($hours * $this->cellWidth) + $this->labelWidth + ($this->padding * 2) + 20;
        $height = ($totalRows * $this->cellHeight) + $this->headerHeight + ($this->padding * 2) + $cardsHeight;

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
        $updateDateTime = $schedule->fetched_at->format('d.m.Y H:i');

        // Іконка блискавки
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#FCD34D'));
        $draw->setFont('DejaVu-Sans');
        $draw->setFontSize(28);
        $draw->setTextAlignment(\Imagick::ALIGN_CENTER);
        $draw->annotation($width / 2, 30, '⚡');
        $image->drawImage($draw);

        // Заголовок білим кольором по центру
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#FFFFFF'));
        $draw->setFont('DejaVu-Sans-Bold');
        $draw->setFontSize(20);
        $draw->setTextAntialias(true);
        $draw->setTextAlignment(\Imagick::ALIGN_CENTER);
        $draw->annotation($width / 2, 55, 'Графік відключень електроенергії в місті Полтава');
        $image->drawImage($draw);

        // Дата по центру
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#FCD34D'));
        $draw->setFont('DejaVu-Sans-Bold');
        $draw->setFontSize(16);
        $draw->setTextAlignment(\Imagick::ALIGN_CENTER);
        $draw->annotation($width / 2, 78, "📅 {$date}");
        $image->drawImage($draw);

        $startX = $this->padding + $this->labelWidth;
        $startY = $this->headerHeight + 110; // Збільшено відступ для заголовків годин

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

            // "з"
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#000000'));
            $draw->setFont('DejaVu-Sans');
            $draw->setFontSize(15);
            $draw->setTextAntialias(true);
            $draw->annotation($x + 12, $startY - 80, 'з');
            $image->drawImage($draw);

            // "00" (початкова година без :00)
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#000000'));
            $draw->setFont('DejaVu-Sans-Bold');
            $draw->setFontSize(15);
            $fromTime = sprintf('%02d', $hour);
            $draw->annotation($x + 7, $startY - 62, $fromTime);
            $image->drawImage($draw);

            // "по"
            $toHour = ($hour + 1) % 24;
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#000000'));
            $draw->setFont('DejaVu-Sans');
            $draw->setFontSize(15);
            $draw->annotation($x + 9, $startY - 42, 'по');
            $image->drawImage($draw);

            // "01" (кінцева година без :00)
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#000000'));
            $draw->setFont('DejaVu-Sans-Bold');
            $draw->setFontSize(15);
            $toTime = sprintf('%02d', $toHour);
            $draw->annotation($x + 7, $startY - 24, $toTime);
            $image->drawImage($draw);
        }

        // Малюємо дані по чергах
        $currentY = $startY;
        $queueStats = []; // Для збору статистики

        foreach ($groupedData as $queueName => $subqueues) {
            foreach ($subqueues as $subqueueData) {
                $subqueue = $subqueueData['subqueue'];

                // Підраховуємо статистику
                $offCount = count(array_filter($subqueueData['hourly_status'], fn ($s) => $s === 'off'));
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

                // Додаємо яскраву вертикальну смугу зліва для ідентифікації
                $draw = new ImagickDraw;
                $draw->setFillColor(new ImagickPixel($bgColor));
                $draw->rectangle($this->padding, $currentY, $this->padding + 8, $currentY + $this->cellHeight);
                $image->drawImage($draw);

                // Номер черги великим шрифтом
                $draw = new ImagickDraw;
                $draw->setFillColor(new ImagickPixel('#000000')); // Чорний
                $draw->setFont('DejaVu-Sans-Bold');
                $draw->setFontSize(20);
                $label = "{$queueName}.{$subqueue}";
                $draw->annotation($this->padding + 7, $currentY + 21, $label);
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

                    // Ліва половина (0-30 хв) з м'якими але читабельними кольорами
                    $color1 = match ($status1) {
                        'off' => '#E57373',    // Трохи яскравіший червоний
                        'maybe' => '#FFB74D',  // Трохи яскравіший жовтий
                        'on' => '#66BB6A',     // Трохи яскравіший зелений
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
                        'off' => '#E57373',    // Трохи яскравіший червоний
                        'maybe' => '#FFB74D',  // Трохи яскравіший жовтий
                        'on' => '#66BB6A',     // Трохи яскравіший зелений
                        default => '#FFFFFF'
                    };

                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel($color2));
                    $draw->setStrokeColor(new ImagickPixel('#D1D5DB'));
                    $draw->setStrokeWidth(0.5);
                    $draw->rectangle($x + $this->cellWidth / 2, $currentY, $x + $this->cellWidth, $currentY + $this->cellHeight);
                    $image->drawImage($draw);
                }

                $currentY += $this->cellHeight;
            }
        }

        // Зберігаємо висоту де закінчується основний графік (до легенд і карточок)
        $graphEndY = $currentY;

        // Додаємо інформацію про періоди відключень внизу
        $bottomY = $currentY + 30;

        // Компактна легенда
        $legendY = $bottomY;
        $legendX = $this->padding + 10;

        // Фон для легенди
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#F9FAFB'));
        $draw->setStrokeColor(new ImagickPixel('#D1D5DB'));
        $draw->setStrokeWidth(1);
        $draw->rectangle($legendX - 5, $legendY - 5, $width - $this->padding - 5, $legendY + 28);
        $image->drawImage($draw);

        // Легенда
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#000000')); // Чорний
        $draw->setFont('DejaVu-Sans-Bold');
        $draw->setFontSize(20);
        $draw->annotation($legendX, $legendY + 18, 'Легенда:');
        $image->drawImage($draw);

        $legendX += 5;
        // Компактна легенда в один рядок
        $legendY = $bottomY;
        $legendX = $this->padding + 10;

        $legendX += 115;

        // Зелений
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#10B981'));
        $draw->setStrokeColor(new ImagickPixel('#059669'));
        $draw->setStrokeWidth(1);
        $draw->rectangle($legendX, $legendY + 4, $legendX + 30, $legendY + 20);
        $image->drawImage($draw);

        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#000000')); // Чорний
        $draw->setFont('DejaVu-Sans');
        $draw->setFontSize(18);
        $draw->annotation($legendX + 36, $legendY + 20, 'Світло є');
        $image->drawImage($draw);

        // Червоний
        $legendX += 140;
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#DC2626'));
        $draw->setStrokeColor(new ImagickPixel('#B91C1C'));
        $draw->setStrokeWidth(1);
        $draw->rectangle($legendX, $legendY + 4, $legendX + 30, $legendY + 20);
        $image->drawImage($draw);

        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#000000')); // Чорний
        $draw->setFont('DejaVu-Sans');
        $draw->setFontSize(18);
        $draw->annotation($legendX + 36, $legendY + 20, 'Вимкнено');
        $image->drawImage($draw);

        // Жовтий
        $legendX += 150;
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#F59E0B'));
        $draw->setStrokeColor(new ImagickPixel('#D97706'));
        $draw->setStrokeWidth(1);
        $draw->rectangle($legendX, $legendY + 4, $legendX + 30, $legendY + 20);
        $image->drawImage($draw);

        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#000000')); // Чорний
        $draw->setFont('DejaVu-Sans');
        $draw->setFontSize(18);
        $draw->annotation($legendX + 36, $legendY + 20, 'Можливо');
        $image->drawImage($draw);

        // Пояснення
        $legendX += 145;
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#000000')); // Чорний
        $draw->setFont('DejaVu-Sans');
        $draw->setFontSize(17);
        $draw->annotation($legendX, $legendY + 20, '(клітинка = 30 хв)');
        $image->drawImage($draw);

        $bottomY += 45;

        // Заголовок секції
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#000000')); // Чорний
        $draw->setFont('DejaVu-Sans-Bold');
        $draw->setFontSize(18);
        $draw->annotation($this->padding + 10, $bottomY + 10, 'Детальні періоди відключень:');
        $image->drawImage($draw);

        $bottomY += 25; // Зменшено відступ
        $columnWidth = 240; // Ширша карточка для кращої читабельності
        $columnSpacing = 10; // Відступ між карточками
        $currentX = $this->padding + 5; // Відступ зліва
        $currentY = $bottomY;
        $maxQueueHeight = 0;
        $cardsPerRow = 3; // 3 карточки в ряд
        $cardCount = 0; // Лічильник карточок

        // Максимальна ширина для карточок
        $maxCardsWidth = $width - ($this->padding * 2); // Резерв справа і зліва

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
                $cellHeight = 34; // Ще більша висота заголовка
                $topPadding = 10; // Ще більший паддінг зверху
                $bottomPadding = 10; // Ще більший паддінг знизу

                // Об'єднуємо всі періоди та прибираємо знак ⚠️
                $allPeriods = array_merge($periods['off'], $periods['maybe']);
                $allPeriods = array_map(function($period) {
                    return str_replace(' ⚠️', '', $period);
                }, $allPeriods);

                if (empty($allPeriods)) {
                    $allPeriods = ['Немає відключень'];
                }

                $cellHeight += count($allPeriods) * 24 + $topPadding + $bottomPadding; // Ще більша висота рядків

                // Світліші кольори для фону карточок
                $lightBgColors = [
                    '1' => '#FFFACD', // Світло-жовтий
                    '2' => '#F0FFF0', // Світло-зелений
                    '3' => '#FFE4B5', // Світло-помаранчевий
                    '4' => '#E0F7FF', // Світло-блакитний
                    '5' => '#FFF0F5', // Світло-рожевий
                    '6' => '#F3E5F5', // Світло-фіолетовий
                ];
                $cardBgColor = $lightBgColors[$queueName] ?? '#FFFFFF';

                // Малюємо рамку комірки з тінню
                $draw = new ImagickDraw;
                $draw->setStrokeColor(new ImagickPixel('#9CA3AF'));
                $draw->setStrokeWidth(2);
                $draw->setFillColor(new ImagickPixel($cardBgColor)); // Використовуємо світлий колір

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

                // Основна рамка з кольоровим фоном
                $draw->roundRectangle(
                    $currentX,
                    $cellStartY,
                    $currentX + $columnWidth - 3,
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
                    $currentX + $columnWidth - 5,
                    $cellStartY + 46,
                    6, 6
                );
                $image->drawImage($draw);

                // Назва черги (по центру горизонтально і вертикально)
                $draw = new ImagickDraw;
                $draw->setFillColor(new ImagickPixel('#000000')); // Чорний
                $draw->setFont('DejaVu-Sans-Bold');
                $draw->setFontSize(20);
                $draw->setTextAlignment(\Imagick::ALIGN_CENTER);
                // Вирівнювання: центр карточки по X, центр заголовка по Y
                $draw->annotation($currentX + ($columnWidth / 2) - 2, $cellStartY + 28, "Черга {$label}");
                $image->drawImage($draw);

                // Відображаємо періоди у стовпчик з іконками
                $lineY = $cellStartY + 58 + $topPadding; // Ще більший відступ зверху

                foreach ($allPeriods as $period) {
            
                    // Всі періоди показуємо червоним
                    $icon = '🔴';
                    $textColor = '#000000'; // Чорний текст

                    // Центруємо список в карточці
                    if ($period == 'Немає відключень') {
                        $textStartX = $currentX + ($columnWidth / 2) - 120; // Зміщуємо вправо для центрування
                    } else {
                        $textStartX = $currentX + ($columnWidth / 2) - 80; // Зміщуємо вправо для центрування
                    }
                    // Іконка
                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel('#DC2626')); // Червоний колір іконки
                    $draw->setFont('DejaVu-Sans');
                    $draw->setFontSize(17);
                    $draw->annotation($textStartX, $lineY, $icon);
                    $image->drawImage($draw);

                    // Текст періоду (вже без ⚠️)
                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel($textColor)); // Чорний текст
                    $draw->setFont('DejaVu-Sans-Bold'); // Жирний шрифт
                    $draw->setFontSize(17);
                    $draw->annotation($textStartX + 20, $lineY, $period);
                    $image->drawImage($draw);

                    $lineY += 22; // Збільшено вертикальний відступ
                }

                // Переходимо до наступної комірки в стовпчику
                $currentY += $cellHeight + 15; // Вертикальний відступ між карточками в одній черзі
            }

            // Запам'ятовуємо максимальну висоту колонки
            $columnHeight = $currentY - $columnStartY;
            if ($columnHeight > $maxQueueHeight) {
                $maxQueueHeight = $columnHeight;
            }

            // Лічильник карточок (кожна черга = 2 підчерги)
            $cardCount++;

            // Переходимо до наступної колонки (наступної черги)
            $currentX += $columnWidth + $columnSpacing; // З відступом між карточками
            $currentY = $columnStartY; // Повертаємося на початок для наступної колонки

            // Якщо розмістили 3 черги (1,2,3), переходимо на новий рядок
            if ($cardCount >= $cardsPerRow) {
                $currentX = $this->padding + 5; // Повертаємось до початку рядка
                $currentY = $columnStartY + $maxQueueHeight + 20; // Новий рядок з відступом
                $maxQueueHeight = 0;
                $cardCount = 0; // Скидаємо лічильник
            }
        }

        // Статистику прибрано для компактності

        // Додаємо ватермарк по діагоналі - тільки на графіку
        $graphStartY = $this->headerHeight + 110;
        $graphHeight = $graphEndY - $graphStartY; // Висота тільки графіка
        $graphWidth = $width; // Повна ширина
        
        // Створюємо окреме зображення для ватермарку
        $watermark = new Imagick();
        $watermark->newImage($graphWidth, $graphHeight, new ImagickPixel('transparent'));
        $watermark->setImageFormat('png');
        
        $drawWatermark = new ImagickDraw;
        $drawWatermark->setFillColor(new ImagickPixel('#00000030')); // Темніший
        $drawWatermark->setFont('DejaVu-Sans-Bold');
        $drawWatermark->setFontSize(60); // Менший розмір
        $drawWatermark->setTextAlignment(\Imagick::ALIGN_CENTER);
        // Позиція по центру графіка
        $drawWatermark->annotation($graphWidth / 2, $graphHeight / 2, 'ANDROSOVA');
        $watermark->drawImage($drawWatermark);
        
        // Накладаємо ватермарк по центру графіка без обертання
        $image->compositeImage($watermark, Imagick::COMPOSITE_OVER, 0, $graphStartY);
        
        $watermark->clear();
        $watermark->destroy();

        // Додаємо інформацію про оновлення внизу справа
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#6B7280'));
        $draw->setFont('DejaVu-Sans');
        $draw->setFontSize(12);
        $draw->setTextAlignment(\Imagick::ALIGN_RIGHT);
        $draw->annotation($width - $this->padding - 10, $height - 15, "🕐 Останнє оновлення: {$updateDateTime}");
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
        $periods = [];
        $currentPeriod = null;

        for ($i = 0; $i < 48; $i++) {
            $status = $hourlyStatus[$i] ?? 'on';

            if ($status === 'off' || $status === 'maybe') {
                if ($currentPeriod === null) {
                    // Починаємо новий період
                    $currentPeriod = ['start' => $i, 'end' => $i];
                } else {
                    // Продовжуємо поточний період
                    $currentPeriod['end'] = $i;
                }
            } else {
                // Статус 'on' - зберігаємо поточний період якщо є
                if ($currentPeriod !== null) {
                    $formattedPeriod = $this->formatPeriod($currentPeriod['start'], $currentPeriod['end']);
                    $periods[] = $formattedPeriod;
                    $currentPeriod = null;
                }
            }
        }

        // Зберігаємо останній період
        if ($currentPeriod !== null) {
            $formattedPeriod = $this->formatPeriod($currentPeriod['start'], $currentPeriod['end']);
            $periods[] = $formattedPeriod;
        }

        // Повертаємо всі періоди як 'off'
        return [
            'off' => $periods,
            'maybe' => [],
        ];
    }

    /**
     * Конвертує час HH:MM в індекс півгодини
     */
    protected function timeToIndex(string $time): int
    {
        [$hour, $min] = explode(':', $time);
        return (int)$hour * 2 + ((int)$min >= 30 ? 1 : 0);
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

    /**
     * Розраховує необхідну висоту для секції з карточками
     */
    protected function calculateCardsHeight(array $groupedData): int
    {
        $topPadding = 10;
        $bottomPadding = 10;
        $lineHeight = 22; // Висота одного рядка періоду
        $headerHeight = 46; // Висота заголовка карточки
        $cardSpacing = 15; // Відступ між карточками по вертикалі
        
        $maxHeightRow1 = 0; // Максимальна висота першого рядку (черги 1-3)
        $maxHeightRow2 = 0; // Максимальна висота другого рядку (черги 4-6)
        
        $queueIndex = 0;
        
        foreach ($groupedData as $queueName => $subqueues) {
            $queueMaxHeight = 0;
            
            foreach ($subqueues as $subqueueData) {
                $periods = $this->calculateOutagePeriods($subqueueData['hourly_status']);
                $allPeriods = array_merge($periods['off'], $periods['maybe']);
                
                if (empty($allPeriods)) {
                    $allPeriods = ['Немає відключень'];
                }
                
                // Розрахунок висоти однієї карточки
                $cardHeight = $headerHeight + (count($allPeriods) * $lineHeight) + $topPadding + $bottomPadding;
                
                if ($cardHeight > $queueMaxHeight) {
                    $queueMaxHeight = $cardHeight;
                }
            }
            
            // Додаємо висоту + вертикальний відступ між підчергами однієї черги
            $totalQueueHeight = $queueMaxHeight + ($queueMaxHeight + $cardSpacing);
            
            // Визначаємо в який рядок потрапляє черга (1-3 в перший, 4-6 в другий)
            if ($queueIndex < 3) {
                if ($totalQueueHeight > $maxHeightRow1) {
                    $maxHeightRow1 = $totalQueueHeight;
                }
            } else {
                if ($totalQueueHeight > $maxHeightRow2) {
                    $maxHeightRow2 = $totalQueueHeight;
                }
            }
            
            $queueIndex++;
        }
        
        // Загальна висота = легенда + заголовок секції + два рядки карточок + відступи
        $legendHeight = 75; // Легенда + заголовок "Детальні періоди"
        $rowSpacing = 20; // Відступ між рядками карточок
        $bottomMargin = 50; // Відступ знизу для футера
        
        return $legendHeight + $maxHeightRow1 + $rowSpacing + $maxHeightRow2 + $bottomMargin + $this->padding + 120;
    }
}
