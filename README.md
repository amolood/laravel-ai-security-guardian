# Laravel AI Security Guardian

[![Packagist Version](https://img.shields.io/packagist/v/abdalmolood/laravel-ai-security-guardian.svg)](https://packagist.org/packages/abdalmolood/laravel-ai-security-guardian)
[![License](https://img.shields.io/github/license/amolood/laravel-ai-security-guardian.svg)](https://github.com/amolood/laravel-ai-security-guardian)
[![PHP Version](https://img.shields.io/badge/PHP-%5E8.2-777BB4.svg)](https://www.php.net/)
[![Laravel Support](https://img.shields.io/badge/Laravel-10%20%7C%2011%20%7C%2012-FF2D20.svg)](https://laravel.com/)
[![Tests](https://img.shields.io/badge/tests-Pest-0EA5E9.svg)](https://pestphp.com/)

Laravel AI Security Guardian is a defensive security package for Laravel applications. It scans for dependency issues, insecure code patterns, misconfiguration, risky uploads, unsafe routes, and common business logic weaknesses. It also provides a review-first UI, safe report generation, and optional AI-assisted analysis with token-aware safeguards.

## Highlights

- Local security scanners for dependency, configuration, Blade, route, upload, and code risk checks.
- Optional AI-assisted deep scan for higher-level triage and remediation guidance.
- Review-first dashboard for scans, findings, reports, patch suggestions, provider settings, notifications, and health checks.
- Safe auto-fix workflow with explicit review gates.
- Multi-language UI support with RTL output.
- JSON and Markdown report generation.
- Designed to minimize unnecessary AI token usage.

## Requirements

- PHP 8.2 or newer
- Laravel 10, 11, or 12
- A supported AI provider if deep analysis is enabled

## Installation

Install the package with Composer:

```bash
composer require abdalmolood/laravel-ai-security-guardian
```

Publish the package configuration:

```bash
php artisan vendor:publish --tag=ai-security-guardian-config
```

Publish the migrations and run them:

```bash
php artisan vendor:publish --tag=ai-security-guardian-migrations
php artisan migrate
```

Optional: publish the package views and translations if you want to customize them.

```bash
php artisan vendor:publish --tag=ai-security-guardian-views
php artisan vendor:publish --tag=ai-security-guardian-lang
```

## Quick Start

Add the required environment variables:

```env
AI_SECURITY_ENABLED=true
AI_SECURITY_UI_ENABLED=true
AI_SECURITY_UI_PREFIX=ai-security
AI_SECURITY_UI_THEME=auto
AI_SECURITY_UI_LOCALE=en
AI_SECURITY_UI_AVAILABLE_LOCALES=en,ar
AI_SECURITY_UI_RTL_LOCALES=ar

AI_SECURITY_PROVIDER=openai
AI_SECURITY_OPENAI_API_KEY=your_api_key
AI_SECURITY_OPENAI_MODEL=gpt-4.1

AI_SECURITY_AI_ENABLED=true
AI_SECURITY_DEEP_SCAN_ENABLED=true
AI_SECURITY_AI_MAX_FINDINGS=12
AI_SECURITY_AI_MAX_TEXT_LENGTH=360
AI_SECURITY_AI_MAX_REFERENCES=3
AI_SECURITY_AI_MAX_COMPLETION_TOKENS=1200
```

Run a standard scan:

```bash
php artisan ai-security:scan
```

Run an in-depth scan with AI assistance:

```bash
php artisan ai-security:scan --deep
```

The deep scan command also has an alias:

```bash
php artisan ai-security:scan:deep
```

Generate the latest report:

```bash
php artisan ai-security:report
php artisan ai-security:report --format=json
```

Open the dashboard in your browser:

```text
/ai-security
```

## Features

### Security Scanners

The package includes scanners for:

- Composer dependency vulnerabilities
- Dangerous environment and config values
- Unsafe Blade output
- Route and middleware access gaps
- Unsafe file upload flows
- Sensitive PHP patterns and raw SQL usage
- Optional AST-based heuristics for larger application patterns

### Review-First UI

The dashboard is intentionally read-focused:

- Scan history
- Finding triage
- Report generation
- Patch suggestions
- Provider settings
- Scanner settings
- Notification settings
- Health checks
- Help and usage guidance

### Token-Aware AI Usage

The AI pipeline is designed to avoid waste:

- Context is redacted before being sent to a provider
- Findings are compacted and deduplicated
- Request size is capped
- Completion size is capped
- Identical requests can be cached
- Deep analysis runs only when it is explicitly enabled and needed

## AI Providers

The package supports:

- OpenAI
- Gemini
- DeepSeek
- Custom OpenAI-compatible endpoints

Example provider configuration:

OpenAI example:

```env
AI_SECURITY_PROVIDER=openai
AI_SECURITY_OPENAI_API_KEY=your_api_key
AI_SECURITY_OPENAI_MODEL=gpt-4.1
```

Custom provider example:

```env
AI_SECURITY_PROVIDER=custom
AI_SECURITY_CUSTOM_BASE_URL=http://localhost:11434/v1
AI_SECURITY_CUSTOM_API_KEY=local-key
AI_SECURITY_CUSTOM_MODEL=your-model
```

## Scheduler

Daily scans can be enabled through configuration. Laravel's scheduler must still be active in production:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Default scan time:

```env
AI_SECURITY_SCAN_TIME=03:00
```

## Localization and RTL

The package ships with English and Arabic translations.

Locale and RTL behavior can be configured with:

```env
AI_SECURITY_UI_LOCALE=en
AI_SECURITY_UI_AVAILABLE_LOCALES=en,ar
AI_SECURITY_UI_RTL_LOCALES=ar
```

You can switch languages from the UI, and pages will render with the correct direction automatically. RTL is based on the active locale.

## Notifications

Supported notification channels:

- Email
- Telegram
- Slack

Example configuration:

```env
AI_SECURITY_MAIL_ENABLED=true
AI_SECURITY_MAIL_TO=security@yourdomain.com

AI_SECURITY_TELEGRAM_ENABLED=false
AI_SECURITY_TELEGRAM_BOT_TOKEN=
AI_SECURITY_TELEGRAM_CHAT_ID=

AI_SECURITY_SLACK_ENABLED=false
AI_SECURITY_SLACK_WEBHOOK_URL=
```

## Safe Auto-Fix

The package can produce safe fixes for eligible findings, but direct file modification remains gated.

Enable direct fixes only when you have a clear review process:

Edit the published config file:

```php
// config/ai-security-guardian.php
'auto_fix' => [
    'enabled' => true,
    'production_direct_fix' => false,
],
```

Set `production_direct_fix` to `true` only when you want to permit direct file changes for eligible findings.

Run the fixer explicitly when you are ready:

```bash
php artisan ai-security:fix --direct
```

Rollback support is also available:

```bash
php artisan ai-security:rollback {patch_id}
```

## Extending the Package

### Add a Custom Scanner

Implement `ScannerInterface` and register it in your application:

```php
use Abdalmolood\AiSecurityGuardian\Contracts\ScannerInterface;
use Illuminate\Support\Collection;

class MyCustomScanner implements ScannerInterface
{
    public function getName(): string
    {
        return 'My Custom Scanner';
    }

    public function scan(): Collection
    {
        return collect();
    }
}
```

Register the scanner:

```php
app(Abdalmolood\AiSecurityGuardian\Scanners\ScannerManager::class)
    ->registerScanner(new MyCustomScanner());
```

### Add a Custom AI Provider

Implement `AiProviderInterface` to plug in another provider:

```php
use Abdalmolood\AiSecurityGuardian\Contracts\AiProviderInterface;
use Abdalmolood\AiSecurityGuardian\DTO\AiResponse;

class MyCustomAiProvider implements AiProviderInterface
{
    public function analyze(string $prompt, array $context = []): AiResponse
    {
        return new AiResponse([]);
    }
}
```

## Example Outputs

Example finding payload:

```json
{
  "title": "APP_DEBUG is enabled in production",
  "severity": "critical",
  "category": "configuration",
  "affected_file": ".env",
  "description": "The APP_DEBUG environment variable is set to true in production.",
  "recommendation": "Set APP_DEBUG=false in the .env file."
}
```

Example report summary:

```markdown
# Security Scan Report

## Summary
- Started At: 2024-01-01 10:00:00
- Total Findings: 1
- Risk Score: 10

## Findings
### APP_DEBUG is enabled in production
- Severity: Critical
- Category: configuration
- Affected File: .env
```

## Testing

Run the test suite:

```bash
vendor/bin/pest
```

## Support and Customization

The package is built to be published and customized. You can override:

- Config
- Views
- Translations
- Migrations

## License

MIT
