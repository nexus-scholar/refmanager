<?php

namespace Nexus\RefManager;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Nexus\RefManager\Formats\RisFormat;
use Nexus\RefManager\Formats\BibTexFormat;
use Nexus\RefManager\Formats\CslJsonFormat;
use Nexus\RefManager\Formats\EndNoteXmlFormat;
use Nexus\RefManager\Formats\VectorJsonlFormat;

class RefManagerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/refmanager.php', 'refmanager');

        $this->app->singleton(FormatManager::class, function ($app) {
            $manager = new FormatManager();
            $manager->register('ris',         RisFormat::class);
            $manager->register('bibtex',      BibTexFormat::class);
            $manager->register('bib',         BibTexFormat::class);  // alias
            $manager->register('csl_json',    CslJsonFormat::class);
            $manager->register('endnote_xml', EndNoteXmlFormat::class);
            $manager->register('vector_jsonl', VectorJsonlFormat::class);
            $manager->register('rag_jsonl',    VectorJsonlFormat::class);
            return $manager;
        });

        $this->app->bind(ReferenceImporter::class);
        $this->app->singleton(ReferenceExporter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->registerApiRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/refmanager.php' => config_path('refmanager.php'),
            ], 'refmanager-config');

            $this->commands([
                Commands\ImportCommand::class,
                Commands\ExportCommand::class,
                Commands\DuplicatesCommand::class,
                Commands\FormatsCommand::class,
                Commands\UiInstallCommand::class,
            ]);
        }
    }

    private function registerApiRoutes(): void
    {
        $prefix = trim((string) config('refmanager.api.prefix', 'api/refmanager'), '/');
        $middleware = config('refmanager.api.middleware', ['api']);

        Route::middleware($middleware)
            ->prefix($prefix)
            ->group(__DIR__.'/../routes/api.php');
    }
}
