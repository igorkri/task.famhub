<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected string $botToken;

    protected string $defaultChatId;

    protected string $devChatId = '841714438';

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->defaultChatId = config('services.telegram.chat_id');
    }

    /**
     * Відправка фото з підписом
     */
    public function sendPhoto(
        string $imagePath,
        string $caption = '',
        ?string $chatId = null,
        bool $sendToDev = false
    ): bool {
        if (! $this->isConfigured()) {
            Log::warning('Telegram bot token or chat ID not configured');

            return false;
        }

        $chatId = $chatId ?? $this->defaultChatId;

        try {
            $response = $this->sendPhotoRequest($imagePath, $caption, $chatId);

            if ($sendToDev) {
                $this->sendPhotoRequest($imagePath, $caption, $this->devChatId);
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Exception sending Telegram photo', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Відправка текстового повідомлення
     */
    public function sendMessage(
        string $message,
        ?string $chatId = null,
        string $parseMode = 'HTML',
        bool $sendToDev = false
    ): bool {
        if (! $this->isConfigured()) {
            Log::warning('Telegram bot token or chat ID not configured');

            return false;
        }

        $chatId = $chatId ?? $this->defaultChatId;

        try {
            $response = Http::post($this->getApiUrl('sendMessage'), [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => $parseMode,
            ]);

            if ($sendToDev) {
                Http::post($this->getApiUrl('sendMessage'), [
                    'chat_id' => $this->devChatId,
                    'text' => $message,
                    'parse_mode' => $parseMode,
                ]);
            }

            if ($response->successful()) {
                Log::info('Telegram message sent', ['chat_id' => $chatId]);

                return true;
            } else {
                Log::error('Failed to send Telegram message', [
                    'chat_id' => $chatId,
                    'response' => $response->body(),
                ]);

                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception sending Telegram message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Відправка документа
     */
    public function sendDocument(
        string $documentPath,
        string $caption = '',
        ?string $chatId = null,
        bool $sendToDev = false
    ): bool {
        if (! $this->isConfigured()) {
            Log::warning('Telegram bot token or chat ID not configured');

            return false;
        }

        $chatId = $chatId ?? $this->defaultChatId;

        try {
            $response = Http::attach(
                'document',
                file_get_contents($documentPath),
                basename($documentPath)
            )->post($this->getApiUrl('sendDocument'), [
                'chat_id' => $chatId,
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ]);

            if ($sendToDev) {
                Http::attach(
                    'document',
                    file_get_contents($documentPath),
                    basename($documentPath)
                )->post($this->getApiUrl('sendDocument'), [
                    'chat_id' => $this->devChatId,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                ]);
            }

            if ($response->successful()) {
                Log::info('Telegram document sent', ['chat_id' => $chatId]);

                return true;
            } else {
                Log::error('Failed to send Telegram document', [
                    'chat_id' => $chatId,
                    'response' => $response->body(),
                ]);

                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception sending Telegram document', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Перевірка конфігурації
     */
    protected function isConfigured(): bool
    {
        return ! empty($this->botToken) && ! empty($this->defaultChatId);
    }

    /**
     * Отримання URL API
     */
    protected function getApiUrl(string $method): string
    {
        return "https://api.telegram.org/bot{$this->botToken}/{$method}";
    }

    /**
     * Відправка фото запиту
     */
    protected function sendPhotoRequest(string $imagePath, string $caption, string $chatId): Response
    {
        $response = Http::attach(
            'photo',
            file_get_contents($imagePath),
            basename($imagePath)
        )->post($this->getApiUrl('sendPhoto'), [
            'chat_id' => $chatId,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        ]);

        if ($response->successful()) {
            Log::info('Telegram photo sent', ['chat_id' => $chatId]);
        } else {
            Log::error('Failed to send Telegram photo', [
                'chat_id' => $chatId,
                'response' => $response->body(),
            ]);
        }

        return $response;
    }
}
