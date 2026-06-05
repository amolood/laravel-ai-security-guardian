<?php

namespace Abdalmolood\AiSecurityGuardian\AI\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Abdalmolood\AiSecurityGuardian\Contracts\AiProviderInterface;
use Abdalmolood\AiSecurityGuardian\DTO\AiResponse;
use Abdalmolood\AiSecurityGuardian\DTO\Finding;
use Abdalmolood\AiSecurityGuardian\Enums\Severity;
use Abdalmolood\AiSecurityGuardian\AI\PromptBuilder;
use Abdalmolood\AiSecurityGuardian\AI\ContextRedactor;

class OpenAiProvider implements AiProviderInterface
{
    public function __construct(
        protected string $apiKey,
        protected string $model,
        protected PromptBuilder $promptBuilder,
        protected ContextRedactor $redactor,
        protected int $timeout = 120,
        protected int $retries = 3,
        protected int $maxCompletionTokens = 1200,
        protected int $cacheTtlMinutes = 1440
    ) {}

    public function analyze(string $prompt, array $context = []): AiResponse
    {
        $redactedContext = $this->redactor->redactArray($context);
        $fullPrompt = $this->promptBuilder->buildPrompt($prompt, $redactedContext, [
            'max_findings' => config('ai-security-guardian.ai.max_findings_per_request', 12),
            'max_text_length' => config('ai-security-guardian.ai.max_text_length', 360),
            'max_references' => config('ai-security-guardian.ai.max_references', 3),
        ]);
        $systemPrompt = $this->promptBuilder->getSystemPrompt();
        $cacheKey = 'ai-security-guardian.ai.' . sha1(json_encode([
            'model' => $this->model,
            'prompt' => $prompt,
            'full_prompt' => $fullPrompt,
            'system_prompt' => $systemPrompt,
        ], JSON_UNESCAPED_SLASHES));

        $payload = Cache::remember($cacheKey, now()->addMinutes(max(1, $this->cacheTtlMinutes)), function () use ($systemPrompt, $fullPrompt) {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeout)
                ->retry($this->retries, 1000)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $fullPrompt],
                    ],
                    'temperature' => 0.0,
                    'max_completion_tokens' => $this->maxCompletionTokens,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->failed()) {
                throw new \Exception('OpenAI API request failed: ' . $response->body());
            }

            $data = $response->json();
            $rawContent = $data['choices'][0]['message']['content'] ?? '[]';
            $decoded = json_decode($rawContent, true) ?? [];

            if (isset($decoded['findings'])) {
                $decoded = $decoded['findings'];
            }

            $findings = [];
            foreach ($decoded as $item) {
                $severity = match (strtolower($item['severity'] ?? 'info')) {
                    'critical' => Severity::CRITICAL,
                    'high' => Severity::HIGH,
                    'medium' => Severity::MEDIUM,
                    'low' => Severity::LOW,
                    default => Severity::INFO,
                };

                $findings[] = [
                    'title' => $item['title'] ?? 'Unknown Issue',
                    'description' => $item['description'] ?? 'No description provided',
                    'severity' => $severity->value,
                    'category' => $item['category'] ?? 'general',
                    'affected_file' => $item['affected_file'] ?? null,
                    'affected_line' => $item['affected_line'] ?? null,
                    'recommended_fix' => $item['recommended_fix'] ?? null,
                    'safe_auto_fix_allowed' => $item['safe_auto_fix_allowed'] ?? false,
                    'human_review_required' => $item['human_review_required'] ?? true,
                    'business_impact' => $item['business_impact'] ?? null,
                    'technical_impact' => $item['technical_impact'] ?? null,
                    'test_plan' => $item['test_plan'] ?? null,
                    'references' => $item['references'] ?? [],
                ];
            }

            return [
                'findings' => $findings,
                'raw_response' => $rawContent,
                'metadata' => [
                    'usage' => $data['usage'] ?? [],
                ],
            ];
        });
        
        $findings = array_map(function (array $item) {
            return new Finding(
                title: $item['title'] ?? 'Unknown Issue',
                description: $item['description'] ?? 'No description provided',
                severity: Severity::tryFrom($item['severity'] ?? 'info') ?? Severity::INFO,
                category: $item['category'] ?? 'general',
                affectedFile: $item['affected_file'] ?? null,
                affectedLine: $item['affected_line'] ?? null,
                recommendation: $item['recommended_fix'] ?? null,
                safeAutoFixAllowed: $item['safe_auto_fix_allowed'] ?? false,
                humanReviewRequired: $item['human_review_required'] ?? true,
                businessImpact: $item['business_impact'] ?? null,
                technicalImpact: $item['technical_impact'] ?? null,
                testPlan: $item['test_plan'] ?? null,
                references: $item['references'] ?? []
            );
        }, $payload['findings']);

        return new AiResponse($findings, $payload['raw_response'], $payload['metadata']);
    }
}
