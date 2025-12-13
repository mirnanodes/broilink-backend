<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected $botToken;
    protected $apiUrl;

    public function __construct()
    {
        $this->botToken = env('TELEGRAM_BOT_TOKEN');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Send message to a single chat
     *
     * @param string $chatId - Telegram chat ID
     * @param string $message - Message text
     * @param string $parseMode - Parse mode (HTML, Markdown, MarkdownV2)
     * @return array Response from Telegram API
     */
    public function sendMessage($chatId, $message, $parseMode = 'HTML')
    {
        try {
            $response = Http::post("{$this->apiUrl}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => $parseMode,
            ]);

            $result = $response->json();

            if (!$result['ok']) {
                Log::error('Telegram send message failed', [
                    'chat_id' => $chatId,
                    'error' => $result
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Telegram API Exception', [
                'chat_id' => $chatId,
                'message' => $e->getMessage()
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Broadcast message to multiple chats
     *
     * @param array $chatIds - Array of Telegram chat IDs
     * @param string $message - Message text
     * @param string $parseMode - Parse mode
     * @return array Summary of broadcast results
     */
    public function broadcastMessage(array $chatIds, $message, $parseMode = 'HTML')
    {
        $results = [
            'total' => count($chatIds),
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($chatIds as $chatId) {
            if (empty($chatId)) {
                continue;
            }

            $response = $this->sendMessage($chatId, $message, $parseMode);

            if ($response['ok']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'chat_id' => $chatId,
                    'error' => $response['error'] ?? $response['description'] ?? 'Unknown error'
                ];
            }

            // Sleep to avoid rate limiting (30 messages per second max)
            usleep(50000); // 50ms delay = 20 messages/second
        }

        return $results;
    }

    /**
     * Get bot info
     *
     * @return array Bot information
     */
    public function getBotInfo()
    {
        try {
            $response = Http::get("{$this->apiUrl}/getMe");
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Telegram getMe Exception', [
                'message' => $e->getMessage()
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format alert message for farm status
     *
     * @param string $farmName
     * @param string $status
     * @param array $sensorData
     * @return string Formatted message
     */
    public function formatFarmAlert($farmName, $status, $sensorData = [])
    {
        $statusEmoji = match(strtolower($status)) {
            'normal' => '✅',
            'waspada' => '⚠️',
            'bahaya' => '🚨',
            default => 'ℹ️'
        };

        $message = "<b>{$statusEmoji} NOTIFIKASI KANDANG</b>\n\n";
        $message .= "<b>Kandang:</b> {$farmName}\n";
        $message .= "<b>Status:</b> " . strtoupper($status) . "\n\n";

        if (!empty($sensorData)) {
            $message .= "<b>📊 Data Sensor:</b>\n";

            if (isset($sensorData['temperature'])) {
                $message .= "🌡 Suhu: {$sensorData['temperature']}°C\n";
            }

            if (isset($sensorData['humidity'])) {
                $message .= "💧 Kelembapan: {$sensorData['humidity']}%\n";
            }

            if (isset($sensorData['ammonia'])) {
                $message .= "💨 Amonia: {$sensorData['ammonia']} ppm\n";
            }
        }

        $message .= "\n⏰ " . now()->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') . " WIB";

        return $message;
    }

    /**
     * Format broadcast announcement message
     *
     * @param string $title
     * @param string $content
     * @return string Formatted message
     */
    public function formatAnnouncement($title, $content)
    {
        $message = "<b>📢 PENGUMUMAN</b>\n\n";
        $message .= "<b>{$title}</b>\n\n";
        $message .= $content . "\n\n";
        $message .= "—\n";
        $message .= "BroiLink Farm Management System\n";
        $message .= "⏰ " . now()->timezone('Asia/Jakarta')->format('d/m/Y H:i:s') . " WIB";

        return $message;
    }
}
