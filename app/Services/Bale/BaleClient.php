<?php

namespace App\Services\Bale;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * A tiny Bale Bot API client (Bale speaks the Telegram Bot API).
 */
class BaleClient
{
    public function configured(): bool
    {
        return filled(config('bale.token'));
    }

    /**
     * @param  array<string, mixed>|null  $replyMarkup
     * @return array<string, mixed>|null
     */
    public function sendMessage(string $chatId, string $text, ?array $replyMarkup = null, ?string $parseMode = 'HTML'): ?array
    {
        if (! $this->configured()) {
            Log::warning('Bale token is not configured; the message was not sent.', ['chat_id' => $chatId, 'text' => $text]);

            return null;
        }

        $payload = ['chat_id' => $chatId, 'text' => $text];

        if ($parseMode !== null) {
            $payload['parse_mode'] = $parseMode;
        }

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        $response = $this->request()->post($this->endpoint('sendMessage'), $payload);

        if ($response->failed()) {
            Log::error('Bale sendMessage failed.', ['status' => $response->status(), 'body' => $response->body()]);

            return null;
        }

        return $response->json();
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text = ''): void
    {
        if (! $this->configured()) {
            return;
        }

        $this->request()->post($this->endpoint('answerCallbackQuery'), array_filter([
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
        ]));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getUpdates(int $offset = 0, int $timeout = 25): array
    {
        if (! $this->configured()) {
            return [];
        }

        $response = $this->request()
            ->timeout($timeout + 10)
            ->get($this->endpoint('getUpdates'), ['offset' => $offset, 'timeout' => $timeout]);

        return $response->successful() ? (array) $response->json('result', []) : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function setWebhook(?string $url): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        $response = $this->request()->post($this->endpoint('setWebhook'), ['url' => $url]);

        return $response->successful() ? (array) $response->json() : null;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('bale.base_url'), '/'))
            ->acceptJson()
            ->asJson()
            ->timeout(20);
    }

    private function endpoint(string $method): string
    {
        return 'bot'.config('bale.token').'/'.$method;
    }
}
