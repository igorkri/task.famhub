<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;

class PowerOutageParserService
{
    /**
     * Парсинг HTML и извлечение данных о графике отключений.
     */
    public function parse(string $html): array
    {
        if (empty(trim($html))) {
            throw new \InvalidArgumentException('HTML content is empty');
        }

        $dom = new DOMDocument;
        $success = @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

        if (! $success) {
            throw new \RuntimeException('Failed to parse HTML');
        }

        $xpath = new DOMXPath($dom);

        return [
            'description' => $this->extractDescription($xpath),
            'periods' => $this->extractPeriods($xpath),
            'schedule_data' => $this->extractScheduleData($xpath),
            'fetched_at' => now(),
        ];
    }

    /**
     * Извлекает описание (текст с информацией о датах и объёмах).
     */
    protected function extractDescription(DOMXPath $xpath): ?string
    {
        $nodes = $xpath->query("//div[@class='gpvinfodetail']");
        if ($nodes->length === 0) {
            return null;
        }

        // Получаем весь текст до таблицы
        $description = '';
        foreach ($nodes->item(0)->childNodes as $child) {
            if ($child->nodeName === 'div' && strpos($child->getAttribute('style'), 'overflow-x') !== false) {
                break;
            }
            if ($child->nodeType === XML_TEXT_NODE || $child->nodeName === 'b' || $child->nodeName === 'br') {
                $description .= $child->textContent;
            }
        }

        return trim($description);
    }

    /**
     * Извлекает периоды и объёмы отключений.
     */
    protected function extractPeriods(DOMXPath $xpath): array
    {
        $nodes = $xpath->query("//div[@class='gpvinfodetail']");
        $periods = [];

        if ($nodes->length === 0) {
            return [];
        }

        $html = '';
        foreach ($nodes->item(0)->childNodes as $child) {
            if ($child->nodeName === 'div' && strpos($child->getAttribute('style') ?? '', 'overflow-x') !== false) {
                break;
            }
            $html .= $nodes->item(0)->ownerDocument->saveHTML($child);
        }

        if (preg_match_all('/з\s+(\d{2}:\d{2})\s+по\s+(\d{2}:\d{2}).*?ГПВ\s+в\s+обсязі\s+<b>([\d.]+)<\/b>\s+черг/u', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $periods[] = [
                    'from' => $match[1],
                    'to' => $match[2],
                    'queues' => (float) $match[3],
                ];
            }
        }

        return $periods;
    }

    /**
     * Извлекает расписание по очередям и часам.
     */
    protected function extractScheduleData(DOMXPath $xpath): array
    {
        $schedule = [];
        $rows = $xpath->query("//table[@class='turnoff-scheduleui-table']//tbody/tr");

        $currentQueue = null;
        foreach ($rows as $row) {
            $queueCell = $xpath->query(".//td[@class='turnoff-scheduleui-table-queue']", $row);
            if ($queueCell->length > 0) {
                $currentQueue = trim($queueCell->item(0)->textContent);
            }

            $subqueueCell = $xpath->query(".//td[@class='turnoff-scheduleui-table-subqueue']", $row);
            if ($subqueueCell->length > 0) {
                $subqueue = trim($subqueueCell->item(0)->textContent);
                $statusCells = $xpath->query(".//td[contains(@class, 'light_')]", $row);

                $statuses = [];
                foreach ($statusCells as $cell) {
                    $class = $cell->getAttribute('class');
                    if (strpos($class, 'light_1') !== false) {
                        $statuses[] = 'on'; // Світло присутнє
                    } elseif (strpos($class, 'light_2') !== false) {
                        $statuses[] = 'off'; // Світло вимкнене
                    } elseif (strpos($class, 'light_3') !== false) {
                        $statuses[] = 'maybe'; // Можливе вимкнення
                    }
                }

                if ($currentQueue && count($statuses) > 0) {
                    $schedule[] = [
                        'queue' => $currentQueue,
                        'subqueue' => $subqueue,
                        'hourly_status' => $statuses,
                    ];
                }
            }
        }

        return $schedule;
    }

    /**
     * Генерирует хеш для определения изменений.
     */
    public function generateHash(array $data): string
    {
        return md5(json_encode($data));
    }
}
