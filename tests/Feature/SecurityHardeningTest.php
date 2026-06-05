<?php

use Abdalmolood\AiSecurityGuardian\AI\ContextRedactor;
use Abdalmolood\AiSecurityGuardian\Fixers\MassAssignmentFixer;
use Abdalmolood\AiSecurityGuardian\Http\Middleware\AuthorizeAiSecurity;
use Abdalmolood\AiSecurityGuardian\Models\SecurityFinding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

/*
 * AuthorizeAiSecurity — fails closed without a host gate or opt-in (#1)
 */

it('denies dashboard access by default when no host gate is defined', function () {
    config()->set('ai-security-guardian.ui.allow_unauthenticated_local', false);

    $middleware = new AuthorizeAiSecurity();

    $call = fn () => $middleware->handle(Request::create('/ai-security'), fn () => response('ok'));

    expect($call)->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('allows access when the local opt-in flag is enabled', function () {
    config()->set('ai-security-guardian.ui.allow_unauthenticated_local', true);

    $middleware = new AuthorizeAiSecurity();
    $response = $middleware->handle(Request::create('/ai-security'), fn () => response('ok'));

    expect($response->getContent())->toBe('ok');
});

/*
 * ContextRedactor — broadened secret coverage (#2)
 */

it('redacts arbitrary sensitive env keys per line', function () {
    $redactor = new ContextRedactor();

    $env = implode("\n", [
        'APP_NAME=Laravel',
        'APP_KEY=base64:supersecretkey',
        'DB_PASSWORD=hunter2',
        'MAILGUN_SECRET=mg-abc123',
        'CUSTOM_API_TOKEN=tok_live_999',
        'STRIPE_SECRET=sk_live_AAAAAAAAAAAAAAAAAA',
        'APP_URL=http://localhost',
    ]);

    $out = $redactor->redact($env);

    expect($out)->toContain('APP_NAME=Laravel');          // non-secret untouched
    expect($out)->toContain('APP_URL=http://localhost');  // non-secret untouched
    expect($out)->toContain('APP_KEY=[REDACTED]');
    expect($out)->toContain('DB_PASSWORD=[REDACTED]');
    expect($out)->toContain('MAILGUN_SECRET=[REDACTED]');
    expect($out)->toContain('CUSTOM_API_TOKEN=[REDACTED]');
    expect($out)->not->toContain('supersecretkey');
    expect($out)->not->toContain('hunter2');
    expect($out)->not->toContain('mg-abc123');
    expect($out)->not->toContain('tok_live_999');
});

it('redacts passwords embedded in connection strings', function () {
    $redactor = new ContextRedactor();

    $out = $redactor->redact('DATABASE_URL=mysql://root:s3cr3t@127.0.0.1:3306/app');

    expect($out)->not->toContain('s3cr3t');
    expect($out)->toContain('[REDACTED]');
});

it('redacts every authorization line, not just the first', function () {
    $redactor = new ContextRedactor();

    $headers = "Authorization: Bearer aaaaaaaaaa\nAuthorization: Bearer bbbbbbbbbb";
    $out = $redactor->redact($headers);

    expect($out)->not->toContain('aaaaaaaaaa');
    expect($out)->not->toContain('bbbbbbbbbb');
});

it('redacts values whose array key signals a secret', function () {
    $redactor = new ContextRedactor();

    $out = $redactor->redactArray([
        'api_key' => 'plain-value-with-no-pattern',
        'note' => 'this is fine',
    ]);

    expect($out['api_key'])->toBe('[REDACTED]');
    expect($out['note'])->toBe('this is fine');
});

it('redacts pem private key blocks of any type', function () {
    $redactor = new ContextRedactor();

    $pem = "-----BEGIN EC PRIVATE KEY-----\nMIINNEROFSECRETS\n-----END EC PRIVATE KEY-----";
    $out = $redactor->redact($pem);

    expect($out)->not->toContain('MIINNEROFSECRETS');
    expect($out)->toContain('[REDACTED]');
});

/*
 * MassAssignmentFixer — AST-verified receiver (#3)
 */

it('rewrites request->all() to validated()', function () {
    $path = base_path('app/Http/Controllers/MaController.php');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, "<?php\n\nclass MaController {\n    public function store(\$request) {\n        return User::create(\$request->all());\n    }\n}\n");

    $finding = new SecurityFinding();
    $finding->category = 'mass_assignment';
    $finding->affected_file = 'app/Http/Controllers/MaController.php';
    $finding->affected_line = 5;

    $result = (new MassAssignmentFixer())->apply($finding);

    expect($result)->toBeTrue();
    expect(File::get($path))->toContain('$request->validated()');

    File::delete($path);
});

it('does NOT touch a Collection->all() call', function () {
    $path = base_path('app/Services/CollectionService.php');
    File::ensureDirectoryExists(dirname($path));
    $code = "<?php\n\nclass CollectionService {\n    public function run(\$users) {\n        return collect(\$users)->all();\n    }\n}\n";
    File::put($path, $code);

    $finding = new SecurityFinding();
    $finding->category = 'mass_assignment';
    $finding->affected_file = 'app/Services/CollectionService.php';
    $finding->affected_line = 5;

    $result = (new MassAssignmentFixer())->apply($finding);

    expect($result)->toBeFalse();
    expect(File::get($path))->toBe($code);          // file untouched
    expect(File::get($path))->toContain('->all()'); // not corrupted

    File::delete($path);
});

it('does NOT touch an unrelated $items->all() call', function () {
    $path = base_path('app/Services/ItemService.php');
    File::ensureDirectoryExists(dirname($path));
    $code = "<?php\n\nclass ItemService {\n    public function run(\$items) {\n        return \$items->all();\n    }\n}\n";
    File::put($path, $code);

    $finding = new SecurityFinding();
    $finding->category = 'mass_assignment';
    $finding->affected_file = 'app/Services/ItemService.php';
    $finding->affected_line = 5;

    $result = (new MassAssignmentFixer())->apply($finding);

    expect($result)->toBeFalse();
    expect(File::get($path))->toBe($code);

    File::delete($path);
});

it('handles request() helper receiver', function () {
    $path = base_path('app/Http/Controllers/HelperController.php');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, "<?php\n\nclass HelperController {\n    public function store() {\n        return User::create(request()->all());\n    }\n}\n");

    $finding = new SecurityFinding();
    $finding->category = 'mass_assignment';
    $finding->affected_file = 'app/Http/Controllers/HelperController.php';
    $finding->affected_line = 5;

    $result = (new MassAssignmentFixer())->apply($finding);

    expect($result)->toBeTrue();
    expect(File::get($path))->toContain('request()->validated()');

    File::delete($path);
});
