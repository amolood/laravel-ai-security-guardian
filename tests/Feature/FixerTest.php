<?php

use Abdalmolood\AiSecurityGuardian\Fixers\EnvHardeningFixer;
use Abdalmolood\AiSecurityGuardian\Fixers\MassAssignmentFixer;
use Abdalmolood\AiSecurityGuardian\Models\SecurityFinding;
use Illuminate\Support\Facades\File;

it('fixes APP_DEBUG in env file safely', function () {
    $envPath = base_path('.env');
    File::put($envPath, "APP_NAME=Laravel\nAPP_DEBUG=true\nAPP_URL=http://localhost");

    $finding = new SecurityFinding();
    $finding->title = 'APP_DEBUG is enabled in production';
    
    $fixer = new EnvHardeningFixer();
    $result = $fixer->apply($finding);

    expect($result)->toBeTrue();
    expect(File::get($envPath))->toContain('APP_DEBUG=false');

    File::delete($envPath);
});

it('fixes mass assignment in controller safely', function () {
    $controllerPath = base_path('app/Http/Controllers/TestController.php');
    File::ensureDirectoryExists(dirname($controllerPath));
    File::put($controllerPath, "<?php\n\nclass TestController {\n    public function store(Request \$request) {\n        User::create(\$request->all());\n    }\n}");

    $finding = new SecurityFinding();
    $finding->category = 'mass_assignment';
    $finding->affected_file = 'app/Http/Controllers/TestController.php';
    $finding->affected_line = 5;

    $fixer = new MassAssignmentFixer();
    $result = $fixer->apply($finding);

    expect($result)->toBeTrue();
    expect(File::get($controllerPath))->toContain('$request->validated()');
    expect(File::get($controllerPath))->not->toContain('$request->all()');

    File::delete($controllerPath);
});
