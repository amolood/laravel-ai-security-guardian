# Laravel AI Security Guardian

A robust, defensive AI-powered Laravel security guardian package that scans Laravel applications daily, detects vulnerabilities, dependency risks, insecure code patterns, production misconfigurations, and business logic issues.

**Developer / Author**: ABDALRAHMAN MOLOOD

---

## 🛡️ Features

- **Composer Audit**: Scans dependencies for known vulnerabilities.
- **Environment & Config Scan**: Detects dangerous configurations in production.
- **Code Analysis**: Finds dangerous PHP functions, raw SQL risks, and mass assignment vulnerabilities.
- **Blade Scanner**: Detects unescaped `{!! !!}` output.
- **Route & Middleware Scanner**: Flags sensitive routes missing authentication or rate-limiting.
- **File Upload Scanner**: Ensures file uploads are validated correctly.
- **AI-Powered Deep Scan**: Uses large language models (LLMs) to intelligently classify risks, explain business impact, and suggest safe fixes without modifying production directly.

---

## 📦 Installation

This package requires **PHP 8.2+** and supports **Laravel 10, 11, and 12**.

```bash
composer require abdalmolood/laravel-ai-security-guardian
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=ai-security-guardian-config
```

Publish the migrations and migrate the database to store scan history:

```bash
php artisan vendor:publish --tag=ai-security-guardian-migrations
php artisan migrate
```

---

## ⚙️ Configuration

The package relies heavily on `.env` configuration. After publishing the config, update your `.env` with the following:

```env
AI_SECURITY_ENABLED=true
AI_SECURITY_PROJECT_TYPE=laravel-saas-erp

# Supported: openai, claude, gemini, deepseek, custom
AI_SECURITY_PROVIDER=openai

AI_SECURITY_OPENAI_API_KEY=your_openai_api_key
AI_SECURITY_OPENAI_MODEL=gpt-4.1

# Notification Settings
AI_SECURITY_MAIL_ENABLED=true
AI_SECURITY_MAIL_TO=security@yourdomain.com

AI_SECURITY_TELEGRAM_ENABLED=false
AI_SECURITY_TELEGRAM_BOT_TOKEN=
AI_SECURITY_TELEGRAM_CHAT_ID=
```

---

## 🤖 Supported AI Providers

The package abstracts the AI interaction so you can use any of the major models:

- **OpenAI / ChatGPT**
- **Anthropic Claude**
- **Google Gemini**
- **DeepSeek**
- **Custom OpenAI-compatible API** (e.g., Local LLM via vLLM/Ollama)

> To use a custom provider, update `AI_SECURITY_PROVIDER=custom` and provide `AI_SECURITY_CUSTOM_BASE_URL` and `AI_SECURITY_CUSTOM_API_KEY`.

---

## 🚀 Usage Commands

Run a fast, local scan without AI analysis:
```bash
php artisan ai-security:scan
```

Run an in-depth scan using the configured AI provider:
```bash
php artisan ai-security:scan --deep
```
*(Alias: `php artisan ai-security:scan:deep`)*

Generate a Markdown or JSON report from the latest scan:
```bash
php artisan ai-security:report
php artisan ai-security:report --format=json
```

---

## ⏱️ Scheduler Setup

To run daily automated scans, the package will automatically hook into Laravel's scheduler if `scan.daily` is `true` in the config. By default, it runs daily at `03:00`.

Make sure your Laravel scheduler cron job is active:
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🔔 Notifications

You can receive notifications via **Email**, **Telegram**, or **Slack**. Configure the credentials in `.env`.
Notifications are triggered when:
- A scan completes successfully.
- A **CRITICAL** finding is detected.

---

## 🔒 Security & Privacy Policy

This package is strictly **defensive**:
- It **does not** create exploits or generate attack payloads.
- It **never** sends your full `.env` file or sensitive secrets (API keys, passwords, bearer tokens) to external AI providers. A robust `ContextRedactor` masks secrets before transmission.
- It **does not** automatically modify production files or schemas without human approval.

### Safe Auto-Fix Policy
1. **Safe auto-fix**: Reserved for safe suggestions like config hardening or minor validation additions. You can apply these automatically using the fixer command.
2. **Needs human review**: Core business logic, tenant isolation, and payment gateways.
3. **Never auto-fix**: Changing database schema, rotating keys, or deleting data.

### Direct File Auto-Fixing
The package includes a strictly controlled auto-fixer that can directly edit files (e.g., `.env` or Controllers) for eligible "Safe Auto-Fix" findings.

To use it:
1. Ensure `AI_SECURITY_AUTO_FIX_PRODUCTION_DIRECT_FIX=true` is set in your `.env`.
2. Run the fix command with the explicit direct flag:
```bash
php artisan ai-security:fix --direct
```

---

## 🛠️ Extending the Guardian

### Add a Custom Scanner

Create a class implementing `Abdalmolood\AiSecurityGuardian\Contracts\ScannerInterface`:

```php
use Abdalmolood\AiSecurityGuardian\Contracts\ScannerInterface;
use Illuminate\Support\Collection;

class MyCustomScanner implements ScannerInterface {
    public function getName(): string { return 'My Custom Scanner'; }
    public function scan(): Collection {
        // Return a collection of DTO\Finding objects
        return collect();
    }
}
```

Register it in your AppServiceProvider:
```php
app(ScannerManager::class)->registerScanner(new MyCustomScanner());
```

### Add a Custom AI Provider

Implement `Abdalmolood\AiSecurityGuardian\Contracts\AiProviderInterface`:

```php
use Abdalmolood\AiSecurityGuardian\Contracts\AiProviderInterface;
use Abdalmolood\AiSecurityGuardian\DTO\AiResponse;

class MyCustomAi implements AiProviderInterface {
    public function analyze(string $prompt, array $context = []): AiResponse {
        // Return structured AiResponse
    }
}
```
Register it via the Service Provider or resolve it dynamically.

---

## 📝 Example Finding & Report

**Example Finding:**
```json
{
    "title": "APP_DEBUG is enabled in production",
    "severity": "critical",
    "category": "configuration",
    "affected_file": ".env",
    "description": "The APP_DEBUG environment variable is set to true in a production environment.",
    "recommendation": "Set APP_DEBUG=false in the .env file."
}
```

**Markdown Report Example:**
```markdown
# Security Scan Report
## Summary
- Started At: 2024-01-01 10:00:00
- Total Findings: 1
- Risk Score: 10

## Findings
### 1. APP_DEBUG is enabled in production
- Severity: 🔴 CRITICAL
- Category: configuration
- Affected File: .env
...
```

---

## License

The MIT License (MIT).
