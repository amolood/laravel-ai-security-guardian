<?php

namespace Abdalmolood\AiSecurityGuardian\Notifications;

use Illuminate\Support\Facades\Mail;
use Abdalmolood\AiSecurityGuardian\Contracts\NotifierInterface;
use Abdalmolood\AiSecurityGuardian\DTO\ScanResult;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;

class MailNotifier implements NotifierInterface
{
    public function notifyScanCompleted(ScanResult $result): void
    {
        if (!config('ai-security-guardian.notifications.mail.enabled')) {
            return;
        }

        $to = config('ai-security-guardian.notifications.mail.to');
        if (!$to) {
            return;
        }

        // Just a simple raw mail for MVP, normally this would use Mailable
        Mail::raw("A security scan has completed. Total findings: {$result->findings->count()}", function ($message) use ($to) {
            $message->to($to)
                    ->subject('Security Scan Completed - AI Security Guardian');
        });
    }

    public function notifyCriticalFinding(Finding $finding): void
    {
        if (!config('ai-security-guardian.notifications.mail.enabled')) {
            return;
        }

        $to = config('ai-security-guardian.notifications.mail.to');
        if (!$to) {
            return;
        }

        $body = "CRITICAL SECURITY FINDING DETECTED\n\n";
        $body .= "Title: {$finding->title}\n";
        $body .= "Category: {$finding->category}\n";
        $body .= "Affected File: {$finding->affectedFile}\n";
        $body .= "\nDescription:\n{$finding->description}\n";
        
        Mail::raw($body, function ($message) use ($to) {
            $message->to($to)
                    ->subject('CRITICAL SECURITY ALERT - AI Security Guardian');
        });
    }
}
