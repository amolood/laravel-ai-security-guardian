# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A **distributable Composer package** (not a Laravel application) — a defensive security toolkit for Laravel apps. It is consumed by host applications via auto-discovery (`extra.laravel.providers`). There is no `app/`, `artisan`, or `.env`; the host app provides those. Tests run against a Testbench-provided skeleton Laravel app.

Namespace: `Abdalmolood\AiSecurityGuardian\` → `src/`. Tests: `Abdalmolood\AiSecurityGuardian\Tests\` → `tests/`.

## Commands

```bash
composer install              # install deps (includes orchestra/testbench skeleton)
./vendor/bin/pest             # run the full test suite (Pest, not phpunit directly)
./vendor/bin/pest tests/Feature/FixerTest.php          # single file
./vendor/bin/pest --filter="redacts secrets"           # single test by name
./vendor/bin/phpstan analyse src                        # static analysis (phpstan ^1.10)
```

There is **no `composer.json` scripts block, no `phpunit.xml`/`pest.xml`, no Pint config** — tooling is invoked directly via `./vendor/bin/*`. If you add a test directory beyond `Feature`/`Unit`, update `tests/Pest.php` (the `uses(...)->in(...)` call).

### Test environment notes

`tests/TestCase.php` extends Testbench and, in `getEnvironmentSetUp`, manually `include`s each migration in `database/migrations/` and calls `->up()` directly (it does **not** use `php artisan migrate`). New migrations are picked up automatically by the `glob`, but they must return an anonymous migration object with an `up()` method. The DB connection is forced to `testing` (in-memory by Testbench).

## Architecture

The package has three cooperating layers plus a review UI. Understanding how they hand off is the key to working here.

### 1. Local scanners (`src/Scanners/`) — the source of truth

Every scanner implements `Contracts\ScannerInterface` (`getName()`, `scan(): Collection`) and emits `DTO\Finding` objects. `ScannerManager` just registers and merges them. **Scanners are wired up explicitly in `Console/ScanCommand::handle()`** (a hardcoded list of `registerScanner(new ...)` calls), *not* auto-discovered — adding a new scanner means adding it both as a class and to that list.

Two scanner families:
- **Pattern/config scanners** (`EnvScanner`, `BladeScanner`, `RouteScanner`, `UploadScanner`, `ComposerAuditScanner`) — inspect config/files directly.
- **AST scanners** (`Scanners/Ast/`) — built on `nikic/php-parser`. Each `Ast*Scanner` extends `AbstractAstScanner` and pairs with a node `Visitor` in `Ast/Visitors/` (e.g. `AstTenantScanner` ↔ `TenantIsolationVisitor`). The scanner walks PHP files; the visitor accumulates findings. To add an AST rule, create a Visitor + a thin Scanner wrapper, then register the scanner in `ScanCommand`.

### 2. AI layer (`src/AI/`) — optional triage, opt-in only

AI runs **only** on `ai-security:scan --deep` AND when enabled, configured (api_key present), and there are local findings to review. The pipeline:

`AiManager::provider()` (factory, `match` on config provider) → `OpenAiProvider::analyze()`. Inside `analyze`:
1. `ContextRedactor::redactArray()` strips secrets (APP_KEY, `*_PASSWORD`, Bearer tokens, private keys, etc.) **before** anything leaves the process.
2. `PromptBuilder::compactContext()` dedupes, prioritizes by severity, truncates text, and caps the number of findings (token-cost control — see `config ai.max_*`).
3. The request is cached via `Cache::remember` keyed on a `sha1` of model + prompts (so repeat scans don't re-bill the AI).
4. Response is forced to `response_format: json_object`, temperature 0, parsed into `Finding` DTOs.

When AI returns findings, `ScanCommand` **replaces** the local findings for reporting/persistence with the AI set (scanner_name = `"AI Deep Scan"`). Only `openai` is implemented; `claude`/`gemini`/`deepseek`/`custom` are config stubs + commented `match` arms — adding one means implementing a `Providers\*Provider` and uncommenting/adding the arm in `AiManager`.

### 3. Persistence & fixers

Findings persist to `Models\SecurityScan` → `SecurityFinding` → `SecurityPatch`. Auto-fix is deliberately gated and conservative:
- `Console/FixCommand` requires BOTH the `--direct` flag AND `config auto_fix.production_direct_fix=true`.
- `Fixers\SafeFixManager` only acts on findings with `safe_auto_fix_allowed=true`, maps `finding.category` → a Fixer class (`fixerMap`), **backs up the file to `storage/app/ai-security-backups/` before editing**, and deletes the backup if the fix no-ops or throws. `RollbackCommand` restores from those backups.
- Fixers (`EnvHardeningFixer`, `MassAssignmentFixer`) are intentionally narrow string/regex edits keyed on exact finding titles/line content. They fail closed (return `false`) rather than guess.

### 4. UI & routing

`routes/web.php` (loaded by the provider) serves a review-first dashboard under config `ui.prefix`, protected by `Http/Middleware/AuthorizeAiSecurity` (allows `local`/`testing`, otherwise checks the `viewAiSecurity` Gate) and `SetAiSecurityLocale`. CSS/JS are served through `AssetController` reading from `resources/` (no published asset build step). `Support\Ui` is shared to all views via `View::share`. Multi-locale + RTL is driven entirely by the `ui.*` config keys.

### Service provider wiring (`AiSecurityGuardianServiceProvider`)

`register()` merges config + binds the `ai-security-guardian` singleton (`AiManager`). `boot()` loads routes/views/translations, registers the five `ai-security:*` commands, declares publish tags (`-config`, `-views`, `-lang`, `-migrations`), and — inside `app->booted()` — registers the daily `ai-security:scan` schedule when `scan.daily` is true.

## Conventions specific to this codebase

- **Findings flow as DTOs (`DTO\Finding`), not arrays**, until they hit the AI layer or the DB. `PromptBuilder` normalizes both camelCase (DTO) and snake_case (array/DB) keys — preserve that dual-key handling when touching it.
- **All config is env-driven** under the `ai-security-guardian.*` namespace (`config/ai-security-guardian.php`). New tunables should follow the `AI_SECURITY_*` env-var naming.
- **Token-cost minimization is a design goal**, not an afterthought — the redact → compact → cap → cache chain in the AI layer exists for this reason. Don't bypass it when adding AI features.
- Scanner registration is manual (in `ScanCommand`); fixer registration is via `SafeFixManager::$fixerMap` keyed on finding category.
