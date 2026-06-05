<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Abdalmolood\AiSecurityGuardian\AI\ContextRedactor;
use Abdalmolood\AiSecurityGuardian\AI\PromptBuilder;
use Abdalmolood\AiSecurityGuardian\AI\Providers\OpenAiProvider;

function makeProvider(): OpenAiProvider
{
    return new OpenAiProvider(
        apiKey: 'test-key',
        model: 'gpt-4.1',
        promptBuilder: new PromptBuilder(),
        redactor: new ContextRedactor(),
        timeout: 30,
        retries: 1,
        maxCompletionTokens: 250,
        cacheTtlMinutes: 60
    );
}

it('throws on a 200 response carrying unparseable json and does not cache it', function () {
    Cache::flush();

    Http::fake([
        'api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['content' => 'this is not json {{{']],
            ],
        ], 200),
    ]);

    $provider = makeProvider();
    $context = [['title' => 'X', 'severity' => 'high', 'category' => 'general', 'description' => 'y']];

    // First call must surface the malformed response rather than returning [].
    expect(fn () => $provider->analyze('Review.', $context))
        ->toThrow(RuntimeException::class);

    // Because the bad payload was NOT cached, a retry actually hits the API
    // again (proving an empty result was never stored for the TTL).
    try {
        $provider->analyze('Review.', $context);
    } catch (\Throwable) {
        // expected
    }

    Http::assertSentCount(2);
});

it('returns findings on a well-formed json response', function () {
    Cache::flush();

    Http::fake([
        'api.openai.com/v1/chat/completions' => Http::response([
            'choices' => [
                ['message' => ['content' => json_encode([
                    'findings' => [
                        ['title' => 'Debug on', 'severity' => 'critical', 'category' => 'configuration', 'description' => 'd'],
                    ],
                ])]],
            ],
            'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1],
        ], 200),
    ]);

    $response = makeProvider()->analyze('Review.', [
        ['title' => 'Debug on', 'severity' => 'critical', 'category' => 'configuration', 'description' => 'd'],
    ]);

    expect($response->findings)->toHaveCount(1);
    expect($response->findings[0]->severity->value)->toBe('critical');
});
