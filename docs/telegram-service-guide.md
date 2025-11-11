# TelegramService - Сервіс для роботи з Telegram

## Опис

`TelegramService` - це єдиний сервіс для роботи з Telegram Bot API в додатку. Він надає методи для відправки різних типів повідомлень: текстових, фото та документів.

## Конфігурація

Налаштування знаходяться у `config/services.php`:

```php
'telegram' => [
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'chat_id' => env('TELEGRAM_CHAT_ID'),
],
```

Додайте у `.env`:
```
TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_CHAT_ID=your_default_chat_id
```

## Основні методи

### 1. sendMessage() - Відправка текстових повідомлень

```php
use App\Services\TelegramService;

$telegram = new TelegramService();

// Просте повідомлення
$telegram->sendMessage('Привіт! Це тестове повідомлення.');

// HTML форматування
$telegram->sendMessage(
    message: '<b>Важливо!</b> Повітряна тривога у вашому регіоні.',
    parseMode: 'HTML'
);

// Відправка в конкретний чат
$telegram->sendMessage(
    message: 'Повідомлення для іншого чату',
    chatId: '123456789'
);

// Відправка і в основний чат, і розробнику
$telegram->sendMessage(
    message: 'Повідомлення для всіх',
    sendToDev: true
);
```

### 2. sendPhoto() - Відправка зображень

```php
use App\Services\TelegramService;

$telegram = new TelegramService();

// Відправка фото з підписом
$telegram->sendPhoto(
    imagePath: '/path/to/image.png',
    caption: '📊 Графік відключень на сьогодні'
);

// Відправка в конкретний чат
$telegram->sendPhoto(
    imagePath: '/path/to/image.png',
    caption: 'Опис зображення',
    chatId: '123456789'
);

// Відправка і в основний чат, і розробнику
$telegram->sendPhoto(
    imagePath: '/path/to/image.png',
    caption: 'Важливе зображення',
    sendToDev: true
);
```

### 3. sendDocument() - Відправка документів

```php
use App\Services\TelegramService;

$telegram = new TelegramService();

// Відправка документа
$telegram->sendDocument(
    documentPath: '/path/to/document.pdf',
    caption: '📄 Звіт за місяць'
);

// З додатковими параметрами
$telegram->sendDocument(
    documentPath: '/path/to/file.xlsx',
    caption: 'Таблиця статистики',
    chatId: '123456789',
    sendToDev: true
);
```

## Використання в Jobs

### Приклад: Відправка графіка відключень

```php
<?php

namespace App\Jobs;

use App\Models\PowerOutageSchedule;
use App\Services\PowerOutageImageGenerator;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPowerOutageNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PowerOutageSchedule $schedule
    ) {}

    public function handle(TelegramService $telegram): void
    {
        try {
            $imageGenerator = new PowerOutageImageGenerator;
            $imagePath = $imageGenerator->generate($this->schedule);
            
            $caption = $this->formatCaption();
            
            $telegram->sendPhoto(
                imagePath: $imagePath,
                caption: $caption,
                sendToDev: true
            );
            
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        } catch (\Exception $e) {
            \Log::error('Exception sending power outage notification', [
                'schedule_id' => $this->schedule->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    protected function formatCaption(): string
    {
        // Форматування підпису
    }
}
```

### Приклад: Відправка повідомлень про повітряну тривогу

```php
<?php

namespace App\Jobs;

use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendAirAlertNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $region,
        public bool $isActive,
        public ?string $additionalInfo = null
    ) {}

    public function handle(TelegramService $telegram): void
    {
        $message = $this->formatMessage();
        
        $telegram->sendMessage(
            message: $message,
            sendToDev: true
        );
    }
    
    protected function formatMessage(): string
    {
        if ($this->isActive) {
            $message = "🚨 <b>ПОВІТРЯНА ТРИВОГА!</b>\n\n";
            $message .= "📍 Регіон: <b>{$this->region}</b>\n";
            $message .= "⚠️ <i>Пройдіть до укриття!</i>\n";
        } else {
            $message = "✅ <b>Відбій повітряної тривоги</b>\n\n";
            $message .= "📍 Регіон: <b>{$this->region}</b>\n";
        }
        
        if ($this->additionalInfo) {
            $message .= "\n💬 {$this->additionalInfo}\n";
        }
        
        return $message;
    }
}
```

## Диспетчеризація Jobs

```php
// Відправка графіка відключень
use App\Jobs\SendPowerOutageNotification;

SendPowerOutageNotification::dispatch($schedule);

// Відправка повідомлення про тривогу
use App\Jobs\SendAirAlertNotification;

SendAirAlertNotification::dispatch(
    region: 'Київська область',
    isActive: true,
    additionalInfo: 'Загроза застосування балістичних ракет'
);

// Відбій тривоги
SendAirAlertNotification::dispatch(
    region: 'Київська область',
    isActive: false
);
```

## Можливості для розширення

### 1. Публікація статей у каналі

```php
public function publishArticle(
    string $title,
    string $content,
    ?string $imageUrl = null
): bool {
    $message = "<b>{$title}</b>\n\n{$content}";
    
    if ($imageUrl) {
        return $this->sendPhoto(
            imagePath: $imageUrl,
            caption: $message
        );
    }
    
    return $this->sendMessage($message);
}
```

### 2. Модерація каналу

```php
public function deleteMessage(int $messageId, ?string $chatId = null): bool
{
    $chatId = $chatId ?? $this->defaultChatId;
    
    $response = Http::post($this->getApiUrl('deleteMessage'), [
        'chat_id' => $chatId,
        'message_id' => $messageId,
    ]);
    
    return $response->successful();
}
```

### 3. Відправка опитувань

```php
public function sendPoll(
    string $question,
    array $options,
    ?string $chatId = null
): bool {
    $chatId = $chatId ?? $this->defaultChatId;
    
    $response = Http::post($this->getApiUrl('sendPoll'), [
        'chat_id' => $chatId,
        'question' => $question,
        'options' => json_encode($options),
    ]);
    
    return $response->successful();
}
```

## HTML форматування

Telegram підтримує наступні HTML теги:

- `<b>жирний</b>` або `<strong>жирний</strong>`
- `<i>курсив</i>` або `<em>курсив</em>`
- `<u>підкреслений</u>`
- `<s>закреслений</s>`
- `<code>моноширинний</code>`
- `<pre>блок коду</pre>`
- `<a href="http://example.com">посилання</a>`

Приклад:
```php
$message = "🔌 <b>Графік відключень</b>\n";
$message .= "📅 <i>25.11.2025</i>\n\n";
$message .= "⏰ <u>Періоди відключень:</u>\n";
$message .= "• 08:00 - 12:00\n";
$message .= "• 18:00 - 22:00\n\n";
$message .= "<a href='https://example.com'>Детальніше</a>";
```

## Логування

Сервіс автоматично логує всі операції:

- **Info**: Успішна відправка повідомлень
- **Warning**: Відсутня конфігурація
- **Error**: Помилки відправки

Приклад логів:
```
[2025-11-11 10:30:45] INFO: Telegram photo sent {"chat_id":"123456789"}
[2025-11-11 10:30:50] ERROR: Failed to send Telegram message {"chat_id":"123456789","response":"..."}
```

## Тестування

Для тестування створіть тестовий бот і чат:

1. Створіть бота через [@BotFather](https://t.me/BotFather)
2. Отримайте токен бота
3. Створіть тестовий канал/групу
4. Додайте бота до каналу як адміністратора
5. Отримайте chat_id через [@userinfobot](https://t.me/userinfobot)

## Безпека

- Ніколи не commitте токени в git
- Використовуйте `.env` для зберігання конфіденційних даних
- Обмежте права бота тільки необхідними
- Регулярно оновлюйте токени

## Обмеження Telegram API

- Максимум 30 повідомлень на секунду
- Максимальний розмір файлу: 50 MB
- Максимальна довжина caption: 1024 символи
- Максимальна довжина текстового повідомлення: 4096 символів

## Інтеграція з іншими сервісами

### Air Alert API (Повітряні тривоги)

TelegramService ідеально інтегрується з `AirAlertService` для відправки сповіщень про повітряні тривоги:

```php
use App\Services\AirAlertService;
use App\Services\TelegramService;

$airAlert = new AirAlertService();
$telegram = new TelegramService();

// Отримати статус тривоги
$alert = $airAlert->getAlertByRegion('25'); // Київ

if ($alert && $alert['alert']) {
    $message = "🚨 <b>ПОВІТРЯНА ТРИВОГА!</b>\n\n";
    $message .= "📍 Регіон: <b>{$alert['region_name']}</b>\n";
    $message .= "⚠️ <i>Пройдіть до укриття!</i>";
    
    $telegram->sendMessage($message, sendToDev: true);
}
```

Детальніше про Air Alert Service дивіться у [air-alert-service-guide.md](air-alert-service-guide.md)

## Пов'язана документація

- 📋 [Air Alert Service Guide](air-alert-service-guide.md) - Інтеграція з API повітряних тривог
- 📋 [Power Outage Notifications](power-outage-guide.md) - Графіки відключень електроенергії

