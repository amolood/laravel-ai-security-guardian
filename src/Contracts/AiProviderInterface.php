<?php

namespace Abdalmolood\AiSecurityGuardian\Contracts;

use Abdalmolood\AiSecurityGuardian\DTO\AiResponse;

interface AiProviderInterface
{
    /**
     * Analyze a prompt with optional context and return a structured AiResponse.
     *
     * @param string $prompt
     * @param array $context
     * @return AiResponse
     */
    public function analyze(string $prompt, array $context = []): AiResponse;
}
