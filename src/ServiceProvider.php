<?php

namespace Files13;

use Files13\Models\File;
use Files13\Models\FilePhase;
use Files13\Observers\FileObserver;
use Files13\Observers\FilePhaseObserver;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'files13');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'files13');
        $this->loadJsonTranslationsFrom(__DIR__.'/../resources/lang');

        $this->publishes([
            __DIR__.'/../config/files13.php' => config_path('files13.php'),
        ], 'files13-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/files13'),
        ], 'files13-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/files13'),
        ], 'files13-lang');

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        File::observe(FileObserver::class);
        FilePhase::observe(FilePhaseObserver::class);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/files13.php', 'files13');
    }
}
