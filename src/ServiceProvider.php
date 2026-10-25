<?php

namespace Files13;

use Files13\Models\File;
use Files13\Models\Document;
use Files13\Models\FilePhase;
use Illuminate\Routing\Router;
use Files13\Models\DocumentTrack;
use Files13\Observers\FileObserver;
use Files13\Observers\FilePhaseObserver;
use Files13\Http\Middleware\TrustedOnlyOfficeIp;
use Files13\Http\Middleware\VerifyOnlyOfficeToken;
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

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('files13.trusted-ip', TrustedOnlyOfficeIp::class);
        $router->aliasMiddleware('files13.onlyoffice-token', VerifyOnlyOfficeToken::class);

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/onlyoffice.php');

        File::observe(FileObserver::class);
        FilePhase::observe(FilePhaseObserver::class);

        $this->guardDocuments();
    }

    /**
     * What deleting a document means, wherever the delete comes from:
     *
     * - a document open in the editor is not deleted;
     * - a template copies were made from is not deleted for good;
     * - deleting for good removes the stored content; the tracks stay, with the name;
     * - deleting a file for good first deletes its documents for good.
     */
    private function guardDocuments(): void
    {
        Document::deleting(static fn (Document $document): bool => $document->isForceDeleting() || ! $document->editing);

        Document::forceDeleting(static fn (Document $document): bool => ! $document->copies()->withTrashed()->exists());

        Document::deleted(static function (Document $document): void {
            if (! $document->isForceDeleting()) {
                DocumentTrack::record($document, 'delete');
            }
        });

        Document::forceDeleted(static function (Document $document): void {
            $document->deleteContent();
            DocumentTrack::record($document, 'destroy');
        });

        File::forceDeleting(static function (File $file): void {
            $file->documents()->withTrashed()->get()->each(static function (Document $document): void {
                $document->forceDelete();
            });
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/files13.php', 'files13');
    }
}
