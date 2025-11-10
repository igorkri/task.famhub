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
        $height = ($totalRows * $this->cellHeight) + $this->headerHeight + ($this->padding * 2) + 750; // Збільшена висота для всього контенту

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
        $draw->annotation($centerX + 20, 50, 'Графік відключень електроенергії');
        $image->drawImage($draw);

        // Дата та час оновлення (з меншим відступом)
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#FCD34D'));
        $draw->setFont('DejaVu-Sans-Bold');
        $draw->setFontSize(20);
        $draw->annotation($centerX + 100, 75, "📅 {$date}  •  🕐 Оновлено: {$time}");
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

            // "з 00:00" - менший текст
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#000000')); // Чорний
            $draw->setFont('DejaVu-Sans');
            $draw->setFontSize(16); // Збільшено з 14
            $draw->setTextAntialias(true);
            $fromText = sprintf('з %02d:00', $hour);
            $draw->annotation($x + 18, $startY - 68, $fromText);
            $image->drawImage($draw);

            // "по 01:00" - менший текст
            $toHour = ($hour + 1) % 24;
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#000000')); // Чорний
            $draw->setFont('DejaVu-Sans');
            $draw->setFontSize(16); // Збільшено з 14
            $toText = sprintf('по %02d:00', $toHour);
            $draw->annotation($x + 10, $startY - 48, $toText);
            $image->drawImage($draw);

            // Велика година по центру
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#000000')); // Чорний
            $draw->setFont('DejaVu-Sans-Bold');
            $draw->setFontSize(30); // Збільшено з 26
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
                $draw->setFontSize(26); // Збільшено з 22
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
        $draw->setFontSize(17); // Збільшено з 15
        $draw->annotation($legendX + 5, $legendY + 16, 'Легенда:');
        $image->drawImage($draw);

        $legendX += 95;
        // Компактна легенда в один рядок
        $legendY = $bottomY;
        $legendX = $this->padding + 10;

        $legendX += 95;

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
        $draw->setFontSize(16); // Збільшено з 14
        $draw->annotation($legendX + 36, $legendY + 16, 'Світло є');
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
        $draw->setFontSize(16); // Збільшено з 14
        $draw->annotation($legendX + 36, $legendY + 16, 'Вимкнено');
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
        $draw->setFontSize(16); // Збільшено з 14
        $draw->annotation($legendX + 36, $legendY + 16, 'Можливо');
        $image->drawImage($draw);

        // Пояснення
        $legendX += 145;
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#000000')); // Чорний
        $draw->setFont('DejaVu-Sans');
        $draw->setFontSize(15); // Збільшено з 13
        $draw->annotation($legendX, $legendY + 16, '(клітинка = 30 хв)');
        $image->drawImage($draw);

        $bottomY += 45;

        // Додаємо легенду черг для навігації
        $queueLegendY = $bottomY;
        $queueLegendX = $this->padding + 10;

        // Фон для легенди черг
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#F0F9FF'));
        $draw->setStrokeColor(new ImagickPixel('#BAE6FD'));
        $draw->setStrokeWidth(1);
        $draw->rectangle($queueLegendX - 5, $queueLegendY - 5, $width - $this->padding - 5, $queueLegendY + 30);
        $image->drawImage($draw);

        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#000000'));
        $draw->setFont('DejaVu-Sans-Bold');
        $draw->setFontSize(16);
        $draw->annotation($queueLegendX + 5, $queueLegendY + 18, 'Черги:');
        $image->drawImage($draw);

        $queueLegendX += 75;

        $queueColors = [
            '1' => ['color' => '#FFD700', 'label' => 'Черга 1'],
            '2' => ['color' => '#7CFC00', 'label' => 'Черга 2'],
            '3' => ['color' => '#FF8C00', 'label' => 'Черга 3'],
            '4' => ['color' => '#00BFFF', 'label' => 'Черга 4'],
            '5' => ['color' => '#FF69B4', 'label' => 'Черга 5'],
            '6' => ['color' => '#9370DB', 'label' => 'Черга 6'],
        ];

        foreach ($queueColors as $queue => $data) {
            // Квадратик
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel($data['color']));
            $draw->setStrokeColor(new ImagickPixel('#6B7280'));
            $draw->setStrokeWidth(1);
            $draw->rectangle($queueLegendX, $queueLegendY + 5, $queueLegendX + 20, $queueLegendY + 23);
            $image->drawImage($draw);

            // Текст
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#000000'));
            $draw->setFont('DejaVu-Sans');
            $draw->setFontSize(15);
            $draw->annotation($queueLegendX + 26, $queueLegendY + 18, $data['label']);
            $image->drawImage($draw);

            $queueLegendX += 110;
        }

        $bottomY += 45;

        // Заголовок секції
        $draw = new ImagickDraw;
        $draw->setFillColor(new ImagickPixel('#000000')); // Чорний
        $draw->setFont('DejaVu-Sans-Bold');
        $draw->setFontSize(19); // Збільшено з 17
        $draw->annotation($this->padding + 10, $bottomY, 'Детальні періоди відключень:');
        $image->drawImage($draw);

        $bottomY += 30;
        $columnWidth = 310; // Збалансована ширина
        $currentX = $this->padding + 10;
        $currentY = $bottomY;
        $maxQueueHeight = 0;

        // Залишаємо місце для статистики справа
        $maxCardsWidth = $width - 420; // Збалансований резерв

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
                $cellHeight = 32; // Збалансована висота заголовка

                // Об'єднуємо всі періоди та прибираємо знак ⚠️
                $allPeriods = array_merge($periods['off'], $periods['maybe']);
                $allPeriods = array_map(function($period) {
                    return str_replace(' ⚠️', '', $period);
                }, $allPeriods);

                if (empty($allPeriods)) {
                    $allPeriods = ['Немає відключень'];
                }

                $cellHeight += count($allPeriods) * 26; // Збільшено з 22

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
                    $cellStartY + 32,
                    6, 6
                );
                $image->drawImage($draw);

                // Назва черги
                $draw = new ImagickDraw;
                $draw->setFillColor(new ImagickPixel('#000000')); // Чорний
                $draw->setFont('DejaVu-Sans-Bold');
                $draw->setFontSize(20); // Збільшено з 18
                $draw->annotation($currentX + 15, $cellStartY + 26, "Черга {$label}"); // Паддінг
                $image->drawImage($draw);

                // Відображаємо періоди у стовпчик з іконками
                $lineY = $cellStartY + 55; // Збільшено відступ

                foreach ($allPeriods as $period) {
                    // Всі періоди показуємо червоним
                    $icon = '🔴';
                    $textColor = '#000000'; // Чорний текст

                    // Іконка
                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel('#DC2626')); // Червоний колір іконки
                    $draw->setFont('DejaVu-Sans');
                    $draw->setFontSize(16); // Збільшено
                    $draw->annotation($currentX + 15, $lineY, $icon); // Паддінг
                    $image->drawImage($draw);

                    // Текст періоду (вже без ⚠️)
                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel($textColor)); // Чорний текст
                    $draw->setFont('DejaVu-Sans-Bold'); // Жирний шрифт
                    $draw->setFontSize(17); // Збільшено з 16
                    $draw->annotation($currentX + 38, $lineY, $period); // Паддінг
                    $image->drawImage($draw);

                    $lineY += 26; // Збільшено вертикальний відступ з 22
                }

                // Переходимо до наступної комірки в стовпчику
                $currentY += $cellHeight + 15; // Збільшено відступ між картками
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
        if (! empty($queueStats)) {
            // Позиція статистики - фіксована праворуч
            $statsX = $maxCardsWidth + 15;
            $statsY = $bottomY;

            // Фон для статистики - збільшена висота з паддінгами
            $statsHeight = count(array_filter($queueStats, fn ($h) => $h > 0)) * 40 + 70; // Збільшено для паддінгів
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#FFFFFF'));
            $draw->setStrokeColor(new ImagickPixel('#9CA3AF'));
            $draw->setStrokeWidth(1.5);

            // Тінь
            $shadowDraw = new ImagickDraw;
            $shadowDraw->setFillColor(new ImagickPixel('#00000018'));
            $shadowDraw->roundRectangle($statsX + 2, $statsY + 2, $width - $this->padding - 8, $statsY + $statsHeight + 2, 6, 6);
            $image->drawImage($shadowDraw);

            // Основна рамка
            $draw->roundRectangle($statsX, $statsY, $width - $this->padding - 10, $statsY + $statsHeight, 6, 6);
            $image->drawImage($draw);

            // Заголовок з градієнтом - збільшений
            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#EEF2FF'));
            $draw->roundRectangle($statsX + 2, $statsY + 2, $width - $this->padding - 12, $statsY + 40, 5, 5);
            $image->drawImage($draw);

            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('#000000')); // Чорний
            $draw->setFont('DejaVu-Sans-Bold');
            $draw->setFontSize(20); // Збільшено з 17
            $draw->annotation($statsX + 12, $statsY + 28, 'Статистика відключень');
            $image->drawImage($draw);

            $statsY += 55; // Збільшено відступ від заголовка з 50

            foreach ($queueStats as $queue => $hours) {
                if ($hours > 0) {
                    $percentage = round(($hours / 24) * 100);

                    // Текст черги зліва - збільшений з паддінгом
                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel('#000000')); // Чорний
                    $draw->setFont('DejaVu-Sans-Bold'); // Жирний шрифт
                    $draw->setFontSize(18); // Збільшено з 15
                    $draw->annotation($statsX + 15, $statsY, "Черга {$queue}:"); // Додано паддінг зліва +15
                    $image->drawImage($draw);

                    // Прогрес-бар в одному рядку з текстом - більший з паддінгом
                    $barWidth = 230; // Зменшена ширина для паддінгу справа
                    $barX = $statsX + 125; // Позиція бару з паддінгом
                    $barY = $statsY - 16; // Вирівнювання по вертикалі

                    // Фон прогрес-бару - вищий
                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel('#E5E7EB'));
                    $draw->setStrokeColor(new ImagickPixel('#D1D5DB'));
                    $draw->setStrokeWidth(1);
                    $draw->roundRectangle($barX, $barY, $barX + $barWidth, $barY + 22, 4, 4); // Висота 22 замість 16
                    $image->drawImage($draw);

                    // Заповнення
                    $fillWidth = ($barWidth * $percentage) / 100;
                    $barColor = $percentage > 50 ? '#DC2626' : ($percentage > 25 ? '#F59E0B' : '#10B981');

                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel($barColor));
                    $draw->roundRectangle($barX, $barY, $barX + $fillWidth, $barY + 22, 4, 4);
                    $image->drawImage($draw);

                    // Значення всередині бару - більший шрифт
                    $draw = new ImagickDraw;
                    $draw->setFillColor(new ImagickPixel('#FFFFFF')); // Білий текст всередині бару
                    $draw->setFont('DejaVu-Sans-Bold');
                    $draw->setFontSize(16); // Збільшено з 13
                    $valueText = "{$hours}г ({$percentage}%)";
                    $draw->annotation($barX + 10, $barY + 16, $valueText);
                    $image->drawImage($draw);

                    $statsY += 40; // Ще більший відступ між рядками для паддінгу
                }
            }
        }

        // Додаємо ватермарк по діагоналі - тільки на графіку
        // Створюємо окреме зображення для ватермарку
        $graphStartY = $this->headerHeight + 110;
        $graphHeight = $graphEndY - $graphStartY; // Висота тільки графіка
        $graphWidth = $width - ($this->padding * 2); // Ширина графіка
        
        $watermark = new Imagick();
        $watermark->newImage($graphWidth * 2, $graphHeight * 2, new ImagickPixel('transparent'));
        $watermark->setImageFormat('png');
        
        $drawWatermark = new ImagickDraw;
        $drawWatermark->setFillColor(new ImagickPixel('#00000035')); // Трохи темніше
        $drawWatermark->setFont('DejaVu-Sans-Bold');
        $drawWatermark->setFontSize(120);
        $drawWatermark->setTextAlignment(\Imagick::ALIGN_CENTER);
        $drawWatermark->annotation($graphWidth, $graphHeight, 'ANDROSOVA');
        $watermark->drawImage($drawWatermark);
        
        // Обертаємо зображення ватермарку на -45 градусів
        $watermark->rotateImage(new ImagickPixel('transparent'), -45);
        
        // Накладаємо ватермарк тільки на графік (не на легенди і карточки)
        $image->compositeImage($watermark, Imagick::COMPOSITE_OVER, 
            $this->padding + ($graphWidth - $watermark->getImageWidth()) / 2, 
            $graphStartY + ($graphHeight - $watermark->getImageHeight()) / 2);
        
        $watermark->clear();
        $watermark->destroy();

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
}
