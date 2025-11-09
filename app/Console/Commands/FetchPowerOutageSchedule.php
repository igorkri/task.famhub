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
            // Получаем HTML данные
            $response = Http::asForm()->post('https://www.poe.pl.ua/customs/newgpv-info.php', [
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

            // Проверяем, есть ли уже такие данные
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
}
