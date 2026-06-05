<?php

namespace Abdalmolood\AiSecurityGuardian\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Abdalmolood\AiSecurityGuardian\AiSecurityGuardianServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            AiSecurityGuardianServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('ai-security-guardian.database.connection', 'testing');
        config()->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        foreach (glob(__DIR__.'/../database/migrations/*.php') as $migrationPath) {
            $migration = include $migrationPath;
            $migration->up();
        }
    }
}
