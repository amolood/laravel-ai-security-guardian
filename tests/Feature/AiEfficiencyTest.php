<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Abdalmolood\AiSecurityGuardian\AI\ContextRedactor;
use Abdalmolood\AiSecurityGuardian\AI\PromptBuilder;
use Abdalmolood\AiSecurityGuardian\AI\Providers\OpenAiProvider;

it('compacts ai context before building a prompt', function () {
    config()->set('ai-security-guardian.ai.max_findings_per_request', 3);
    config()->set('ai-security-guardian.ai.max_text_length', 60);
    config()->set('ai-security-guardian.ai.max_references', 2);

    $builder = new PromptBuilder();

    $context = [];
    for ($i = 1; $i <= 10; $i++) {
        $context[] = [
            'title' => "Finding {$i}",
            'severity' => $i <= 2 ? 'critical' : 'medium',
            'category' => 'configuration',
            'description' => str_repeat("Finding {$i} long description ", 20),
            'references' => ['https://example.com/one', 'https://example.com/two', 'https://example.com/three'],
        ];
    }

    $compact = $builder->compactContext($context);

    expect($compact['findings'])->toHaveCount(3);
    expect($compact['truncated_findings'])->toBe(7);
    expect($compact['scan_summary']['critical'])->toBe(2);
    expect(strlen($compact['findings'][0]['description']))->toBeLessThan(120);
    expect($compact['findings'][0]['references'])->toHaveCount(2);
});

it('caches identical openai analysis requests and caps output tokens', function () {
    Cache::flush();

    config()->set('ai-security-guardian.ai.max_findings_per_request', 2);
    config()->set('ai-security-guardian.ai.max_text_length', 40);
    config()->set('ai-security-guardian.ai.max_references', 1);

    Http::fake([
        'api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'findings' => [
                                [
                                    'title' => 'Debug mode enabled',
                                    'severity' => 'critical',
                                    'category' => 'configuration',
                                    'description' => 'Debug is on.',
                                    'recommended_fix' => 'Disable it.',
                                ],
                            ],
                        ]),
                    ],
                ],
            ],
            'usage' => [
                'prompt_tokens' => 111,
                'completion_tokens' => 22,
            ],
        ], 200),
    ]);

    $provider = new OpenAiProvider(
        apiKey: 'test-key',
        model: 'gpt-4.1',
        promptBuilder: new PromptBuilder(),
        redactor: new ContextRedactor(),
        timeout: 30,
        retries: 1,
        maxCompletionTokens: 250,
        cacheTtlMinutes: 60
    );

    $context = [
        ['title' => 'Debug mode enabled', 'severity' => 'critical', 'category' => 'configuration', 'description' => str_repeat('A', 500)],
        ['title' => 'Debug mode enabled', 'severity' => 'critical', 'category' => 'configuration', 'description' => str_repeat('A', 500)],
        ['title' => 'Mass assignment', 'severity' => 'high', 'category' => 'mass_assignment', 'description' => str_repeat('B', 500)],
    ];

    $first = $provider->analyze('Review security findings.', $context);
    $second = $provider->analyze('Review security findings.', $context);

    expect($first->findings)->toHaveCount(1);
    expect($second->findings)->toHaveCount(1);
    Http::assertSentCount(1);
    Http::assertSent(function ($request) {
        $data = $request->data();

        return ($data['max_completion_tokens'] ?? null) === 250
            && strlen($data['messages'][1]['content'] ?? '') < 2000;
    });
});
