<?php

use Abdalmolood\AiSecurityGuardian\Scanners\EnvScanner;
use Abdalmolood\AiSecurityGuardian\Scanners\ContextRedactor;

it('flags APP_DEBUG in production', function () {
    app()->detectEnvironment(fn() => 'production');
    config()->set('app.debug', true);

    $scanner = new EnvScanner();
    $findings = $scanner->scan();

    expect($findings)->not->toBeEmpty();
    expect($findings->first()->title)->toBe('APP_DEBUG is enabled in production');
});

it('redacts sensitive context data', function () {
    $redactor = new \Abdalmolood\AiSecurityGuardian\AI\ContextRedactor();
    
    $context = [
        'env' => "APP_KEY=base64:somethingsecret\nDB_PASSWORD=my_password",
        'headers' => "Authorization: Bearer 1234567890",
    ];

    $redacted = $redactor->redactArray($context);

    expect($redacted['env'])->toContain('APP_KEY=[REDACTED]');
    expect($redacted['env'])->toContain('DB_PASSWORD=[REDACTED]');
    expect($redacted['env'])->not->toContain('somethingsecret');
    expect($redacted['env'])->not->toContain('my_password');
    
    expect($redacted['headers'])->toContain('Authorization: [REDACTED]');
    expect($redacted['headers'])->not->toContain('1234567890');
});
