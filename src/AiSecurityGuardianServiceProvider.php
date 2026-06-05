<?php

namespace Abdalmolood\AiSecurityGuardian;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\View;
use Abdalmolood\AiSecurityGuardian\Console\ScanCommand;
use Abdalmolood\AiSecurityGuardian\Console\DeepScanCommand;
use Abdalmolood\AiSecurityGuardian\Console\ReportCommand;
use Abdalmolood\AiSecurityGuardian\Console\FixCommand;
use Abdalmolood\AiSecurityGuardian\Console\RollbackCommand;
use Abdalmolood\AiSecurityGuardian\AI\AiManager;
use Abdalmolood\AiSecurityGuardian\Support\Ui;

class AiSecurityGuardianServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/ai-security-guardian.php', 'ai-security-guardian'
        );

        $this->app->singleton('ai-security-guardian', function ($app) {
            return new AiManager($app);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'ai-security-guardian');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'ai-security-guardian');

        View::share('ui', $this->app->make(Ui::class));

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/ai-security-guardian.php' => config_path('ai-security-guardian.php'),
            ], 'ai-security-guardian-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/ai-security-guardian'),
            ], 'ai-security-guardian-views');

            $this->publishes([
                __DIR__.'/../resources/lang' => lang_path('vendor/ai-security-guardian'),
            ], 'ai-security-guardian-lang');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'ai-security-guardian-migrations');

            $this->commands([
                ScanCommand::class,
                DeepScanCommand::class,
                ReportCommand::class,
                FixCommand::class,
                RollbackCommand::class,
            ]);
        }

        $this->app->booted(function () {
            if (config('ai-security-guardian.scan.daily')) {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('ai-security:scan')
                         ->dailyAt(config('ai-security-guardian.scan.time'));
            }
        });
    }
}
