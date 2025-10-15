<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAsanaWebhookJob;
use App\Models\AsanaWebhook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsanaWebhookController extends Controller
{
    /**
     * Handle incoming Asana webhook.
     */
    public function handle(Request $request)
    {
        // Asana надсилає заголовок X-Hook-Secret при створенні webhook для верифікації
        if ($request->header('X-Hook-Secret')) {
            // Возвращаем X-Hook-Secret как HTTP-заголовок, как требует Asana
            return response('', 200)->header('X-Hook-Secret', $request->header('X-Hook-Secret'));
        }

        // Верифікація підпису webhook (опціонально, але рекомендовано)
        if (! $this->verifyWebhookSignature($request)) {
            Log::warning('Invalid Asana webhook signature', [
                'ip' => $request->ip(),
                'payload' => $request->all(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // Логування отриманого webhook
        Log::info('Asana webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        // Обробка webhook через Job для асинхронності
        $events = $request->input('events', []);

        foreach ($events as $event) {
            // Оновлюємо статистику webhook в БД
            $this->recordWebhookEvent($event);

            ProcessAsanaWebhookJob::dispatch($event);
        }

        // Asana очікує швидку відповідь 200 OK
        return response()->json(['status' => 'received'], 200);
    }

    /**
     * Record webhook event in database.
     */
    protected function recordWebhookEvent(array $event): void
    {
        $resource = $event['resource'] ?? null;

        if (! $resource || empty($resource['gid'])) {
            return;
        }

        // Шукаємо webhook в БД за resource_gid
        $webhook = AsanaWebhook::where('resource_gid', $resource['gid'])->first();

        if ($webhook) {
            $webhook->recordEvent();
        }
    }

    /**
     * Verify webhook signature (якщо Asana надсилає підпис).
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        // Asana не надсилає HMAC підписи як Stripe/Paddle,
        // але можна перевіряти IP-адреси або використовувати secret token
        $secret = config('services.asana.webhook_secret');

        if (! $secret) {
            // Якщо секрет не налаштований, пропускаємо перевірку
            return true;
        }

        // Можна додати кастомну логіку верифікації
        // Наприклад, перевіряти query параметр ?secret=xxx
        return $request->query('secret') === $secret;
    }
}
