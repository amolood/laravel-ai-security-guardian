<?php

namespace Abdalmolood\AiSecurityGuardian\Notifications;

use Illuminate\Support\Facades\Http;
use Abdalmolood\AiSecurityGuardian\Contracts\NotifierInterface;
use Abdalmolood\AiSecurityGuardian\DTO\ScanResult;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;

class TelegramNotifier implements NotifierInterface
{
    public function notifyScanCompleted(ScanResult $result): void
    {
        if (!config('ai-security-guardian.notifications.telegram.enabled')) {
            return;
        }

        $text = "🛡️ *Security Scan Completed*\n\n";
        $text .= "Total findings: {$result->findings->count()}";

        $this->sendMessage($text);
    }

    public function notifyCriticalFinding(Finding $finding): void
    {
        if (!config('ai-security-guardian.notifications.telegram.enabled')) {
            return;
        }

        $text = "🚨 *CRITICAL SECURITY FINDING*\n\n";
        $text .= "*Title:* {$finding->title}\n";
        $text .= "*Category:* {$finding->category}\n";
        if ($finding->affectedFile) {
            $text .= "*File:* `{$finding->affectedFile}`\n";
        }
        
        $this->sendMessage($text);
    }

    protected function sendMessage(string $text): void
    {
        $token = config('ai-security-guardian.notifications.telegram.bot_token');
        $chatId = config('ai-security-guardian.notifications.telegram.chat_id');

        if (!$token || !$chatId) {
            return;
        }

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }
}
