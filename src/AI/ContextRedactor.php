<?php

namespace Abdalmolood\AiSecurityGuardian\AI;

/**
 * Best-effort redaction of secrets before any context leaves the process and
 * is sent to a third-party AI provider.
 *
 * This is a defence-in-depth layer, NOT a guarantee. Pattern-based redaction
 * cannot catch every secret (e.g. a secret with an unusual name embedded in
 * free text). Prefer never placing raw secrets into scan context in the first
 * place; treat this as a safety net.
 */
class ContextRedactor
{
    /**
     * Regex => replacement. All multiline content is handled with the `m`
     * flag so redaction is applied per-line, not just to the first match.
     *
     * @var array<string, string>
     */
    protected array $patterns = [
        // Any KEY=VALUE env line whose key looks sensitive. Covers
        // APP_KEY, *_PASSWORD, *_SECRET, *_TOKEN, *_API_KEY, *_PRIVATE_KEY,
        // *_DSN, *_CREDENTIALS, etc. The `m` flag anchors per line.
        '/^([A-Z0-9_]*(?:KEY|SECRET|PASSWORD|PASSWD|TOKEN|CREDENTIAL[S]?|DSN|SALT|CIPHER|PRIVATE)[A-Z0-9_]*\s*=).*$/m' => '$1[REDACTED]',

        // Passwords embedded in connection strings / URLs: scheme://user:pass@host
        '#(://[^:/\s]+:)[^@/\s]+(@)#' => '$1[REDACTED]$2',

        // Common provider key formats (matched anywhere, not just env lines).
        '/sk-[A-Za-z0-9_\-]{16,}/' => '[REDACTED]',                 // OpenAI / Stripe secret
        '/sk_live_[A-Za-z0-9]{16,}/' => '[REDACTED]',               // Stripe live
        '/xox[baprs]-[A-Za-z0-9-]{10,}/' => '[REDACTED]',           // Slack
        '/gh[pousr]_[A-Za-z0-9]{20,}/' => '[REDACTED]',             // GitHub token
        '/AKIA[0-9A-Z]{16}/' => '[REDACTED]',                       // AWS access key id
        '/AIza[0-9A-Za-z_\-]{20,}/' => '[REDACTED]',                // Google API key

        // HTTP auth artefacts (per-line via `m`).
        '/(Bearer\s+)[A-Za-z0-9\-\._~\+\/]+=*/i' => '$1[REDACTED]',
        '/^(Authorization:\s*).*$/im' => '$1[REDACTED]',
        '/^(Cookie:\s*).*$/im' => '$1[REDACTED]',
        '/^(Set-Cookie:\s*).*$/im' => '$1[REDACTED]',

        // PEM private key blocks (any type, including EC).
        '/(-----BEGIN (?:[A-Z]+ )?PRIVATE KEY-----).*?(-----END (?:[A-Z]+ )?PRIVATE KEY-----)/s' => '$1[REDACTED]$2',
    ];

    public function redact(string $content): string
    {
        foreach ($this->patterns as $pattern => $replacement) {
            $result = preg_replace($pattern, $replacement, $content);

            // preg_replace returns null on error (e.g. catastrophic input);
            // keep the previous, safer value rather than dropping redaction.
            if ($result !== null) {
                $content = $result;
            }
        }

        return $content;
    }

    public function redactArray(array $context): array
    {
        $redacted = [];
        foreach ($context as $key => $value) {
            if (is_string($value)) {
                $redacted[$key] = $this->redactValueForKey($key, $value);
            } elseif (is_array($value)) {
                $redacted[$key] = $this->redactArray($value);
            } else {
                $redacted[$key] = $value;
            }
        }
        return $redacted;
    }

    /**
     * If the array key itself signals a secret, redact the whole value rather
     * than relying on the value matching a pattern.
     */
    protected function redactValueForKey(string $key, string $value): string
    {
        if (preg_match('/(secret|password|passwd|token|api[_-]?key|private[_-]?key|credential|salt)/i', $key)) {
            return '[REDACTED]';
        }

        return $this->redact($value);
    }
}
