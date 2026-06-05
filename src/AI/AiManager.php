<?php

namespace Abdalmolood\AiSecurityGuardian\AI;

use Illuminate\Contracts\Foundation\Application;
use Abdalmolood\AiSecurityGuardian\Contracts\AiProviderInterface;
use Abdalmolood\AiSecurityGuardian\AI\Providers\OpenAiProvider;

class AiManager
{
    public function __construct(protected Application $app) {}

    public function provider(?string $name = null): AiProviderInterface
    {
        $name = $name ?: config('ai-security-guardian.provider', 'openai');
        $config = config("ai-security-guardian.providers.{$name}", []);

        $promptBuilder = new PromptBuilder();
        $redactor = new ContextRedactor();

        return match ($name) {
            'openai' => new OpenAiProvider(
                $config['api_key'] ?? '',
                $config['model'] ?? 'gpt-4.1',
                $promptBuilder,
                $redactor,
                (int) ($config['timeout'] ?? 120),
                (int) ($config['retries'] ?? 3),
                (int) ($config['max_completion_tokens'] ?? 1200),
                (int) (config('ai-security-guardian.ai.cache_ttl', 1440))
            ),
            // 'claude' => new ClaudeProvider(...),
            // 'gemini' => new GeminiProvider(...),
            // 'deepseek' => new DeepSeekProvider(...),
            // 'custom' => new CustomProvider(...),
            default => throw new \InvalidArgumentException("AI Provider [{$name}] is not supported."),
        };
    }
}
