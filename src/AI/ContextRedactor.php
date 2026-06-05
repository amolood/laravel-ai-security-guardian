<?php

namespace Abdalmolood\AiSecurityGuardian\AI;

class ContextRedactor
{
    protected array $patterns = [
        '/(APP_KEY=)(.*)/' => '$1[REDACTED]',
        '/(_PASSWORD=)(.*)/' => '$1[REDACTED]',
        '/(_SECRET=)(.*)/' => '$1[REDACTED]',
        '/(AWS_SECRET_ACCESS_KEY=)(.*)/' => '$1[REDACTED]',
        '/(STRIPE_SECRET=)(.*)/' => '$1[REDACTED]',
        '/(PAYPAL_SECRET=)(.*)/' => '$1[REDACTED]',
        '/(JWT_SECRET=)(.*)/' => '$1[REDACTED]',
        '/(Bearer\s+)[A-Za-z0-9\-\._~\+\/]+=*/' => '$1[REDACTED]',
        '/(Authorization:\s+.*)/i' => 'Authorization: [REDACTED]',
        '/(Cookie:\s+.*)/i' => 'Cookie: [REDACTED]',
        '/(-----BEGIN (?:RSA |OPENSSH )?PRIVATE KEY-----)(.*?)(-----END (?:RSA |OPENSSH )?PRIVATE KEY-----)/s' => '$1[REDACTED]$3',
    ];

    public function redact(string $content): string
    {
        foreach ($this->patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    public function redactArray(array $context): array
    {
        $redacted = [];
        foreach ($context as $key => $value) {
            if (is_string($value)) {
                $redacted[$key] = $this->redact($value);
            } elseif (is_array($value)) {
                $redacted[$key] = $this->redactArray($value);
            } else {
                $redacted[$key] = $value;
            }
        }
        return $redacted;
    }
}
