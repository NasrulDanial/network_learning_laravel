<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function sendMessage(string $message): bool
    {
        $botToken = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (! $botToken || ! $chatId) {
            Log::warning('Telegram notification failed: missing bot token or chat ID.');
            return false;
        }

        try {
            $response = Http::post(
                "https://api.telegram.org/bot{$botToken}/sendMessage",
                [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ]
            );

            if (! $response->successful()) {
                Log::warning('Telegram notification failed.', [
                    'response' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Exception $exception) {
            Log::error('Telegram notification error.', [
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}