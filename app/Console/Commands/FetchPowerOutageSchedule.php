<?php

namespace App\Console\Commands;

use App\Jobs\SendPowerOutageNotification;
use App\Models\PowerOutageSchedule;
use App\Services\PowerOutageParserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchPowerOutageSchedule extends Command
{
    protected $signature = 'power:fetch-schedule {date?} {--notify : Принудительно отправить уведомление}';

    protected $description = 'Получить и сохранить график отключений электроэнергии';

    public function handle(PowerOutageParserService $parser): int
    {
        $date = $this->argument('date') ?? now()->format('d-m-Y');

        $this->info("Получение графика отключений на {$date}...");

        try {
            // Шаг 1: Проверяем наличие данных через легкий API запрос
            $this->line('Проверка наличия обновлений...');

//            $infoResponse = Http::asForm()
//                ->withHeaders($this->getBrowserHeaders())
//                ->timeout(15)
//                ->retry(2, 100)
//                ->post('https://www.poe.pl.ua/customs/unloading-info.php', [
//                    'seldate' => json_encode(['date_in' => $date]),
//                ]);
//
//            if ($infoResponse->successful()) {
//                $infoData = $infoResponse->json();
//
//                if (! empty($infoData)) {
//                    $this->line('Найдено записей: '.count($infoData));
//
//                    // Проверяем дату создания последнего изменения
//                    $latestCreatedDate = collect($infoData)
//                        ->max('createddate');
//
//                    if ($latestCreatedDate) {
//                        $this->line("Последнее изменение: {$latestCreatedDate}");
//
//                        // Проверяем, есть ли у нас уже данные с такой же датой создания
//                        $scheduleDate = now()->createFromFormat('d-m-Y', $date)->format('Y-m-d');
//                        $existing = PowerOutageSchedule::where('schedule_date', $scheduleDate)
//                            ->latest('fetched_at')
//                            ->first();
//
////                        if ($existing && $existing->metadata && isset($existing->metadata['created_date'])) {
////                            if ($existing->metadata['created_date'] === $latestCreatedDate) {
////                                $this->info('Данные актуальны (по дате создания). Пропуск загрузки HTML.');
////
////                                if ($this->option('notify')) {
////                                    $this->info('Принудительная отправка уведомления (флаг --notify)...');
////                                    SendPowerOutageNotification::dispatchSync($existing);
////                                }
////
////                                return Command::SUCCESS;
////                            }
////                        }
//
//                        $this->line('Обнаружены изменения. Загрузка полного графика...');
//                    }
//                } else {
//                    $this->warn('API не вернул данных. Возможно, график ещё не опубликован.');
//                }
//            } else {
//                $this->warn('Не удалось проверить через API. Пробуем загрузить напрямую...');
//            }

            // Шаг 2: Получаем HTML данные с заголовками браузера для имитации обычного пользователя
            $response = Http::asForm()
                ->withHeaders($this->getBrowserHeaders())
                ->timeout(30)
                ->retry(3, 100)
                ->post('https://www.poe.pl.ua/customs/newgpv-info.php', [
                    'seldate' => json_encode(['date_in' => $date]),
                ]);

            if ($response->failed()) {
                $this->error('Не удалось получить данные с сервера');

                return Command::FAILURE;
            }

            $html = $response->body();

            // Проверяем, что ответ не пустой
            if (empty(trim($html))) {
                $this->error('Получен пустой ответ от сервера. Возможно, данные для даты '.$date.' ещё не доступны.');

                return Command::FAILURE;
            }

            // Парсим HTML
            $parsedData = $parser->parse($html);

            if (empty($parsedData['schedule_data'])) {
                $this->warn('Расписание не найдено в ответе');

                return Command::FAILURE;
            }

            // Генерируем хеш только из schedule_data для проверки изменений
            $hash = $parser->generateHash($parsedData['schedule_data']);

            // Проверяем, есть ли уже такие данныеГрафік на завтра опубліковано!
            $scheduleDate = now()->createFromFormat('d-m-Y', $date)->format('Y-m-d');
            $existing = PowerOutageSchedule::where('schedule_date', $scheduleDate)
                ->latest('fetched_at')
                ->first();

            if ($existing && $existing->hash === $hash) {
                $this->info('Данные не изменились');

                // Если передан флаг --notify, отправляем уведомление даже если данные не изменились
                if ($this->option('notify')) {
                    $this->info('Принудительная отправка уведомления (флаг --notify)...');
                    SendPowerOutageNotification::dispatchSync($existing);
                }

                return Command::SUCCESS;
            }

            // Сохраняем новые данные
            $schedule = PowerOutageSchedule::create([
                'schedule_date' => $scheduleDate,
                'description' => $parsedData['description'],
                'periods' => $parsedData['periods'],
                'schedule_data' => $parsedData['schedule_data'],
                'fetched_at' => $parsedData['fetched_at'],
                'hash' => $hash,
                'metadata' => [
                    'created_date' => $latestCreatedDate ?? null,
                    'api_data' => ! empty($infoData) ? $infoData : null,
                ],
            ]);

            $this->info('График отключений сохранён (ID: '.$schedule->id.')');

            // Определяем, нужно ли отправлять уведомление
            $shouldNotify = false;
            $notifyReason = '';

            if ($this->option('notify')) {
                $shouldNotify = true;
                $notifyReason = 'Принудительная отправка (флаг --notify)';
            } elseif ($existing) {
                $shouldNotify = true;
                $notifyReason = 'Обнаружены изменения в графике';
            } elseif (! $existing) {
                $shouldNotify = true;
                $notifyReason = 'Первое получение графика';
            }

            if ($shouldNotify) {
                $this->info($notifyReason.'. Отправка уведомления...');
                SendPowerOutageNotification::dispatchSync($schedule);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Ошибка: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Получить заголовки браузера для имитации обычного пользователя
     */
    private function getBrowserHeaders(): array
    {
        return [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language' => 'uk-UA,uk;q=0.9,ru;q=0.8,en;q=0.7',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Referer' => 'https://www.poe.pl.ua/',
            'Origin' => 'https://www.poe.pl.ua',
            'Connection' => 'keep-alive',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-User' => '?1',
            'Upgrade-Insecure-Requests' => '1',
            'Cache-Control' => 'max-age=0',
        ];
    }
}
