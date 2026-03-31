<?php

namespace Nexus\RefManager;

use Illuminate\Support\ServiceProvider;
use Nexus\RefManager\Formats\RisFormat;
use Nexus\RefManager\Formats\BibTexFormat;
use Nexus\RefManager\Formats\CslJsonFormat;
use Nexus\RefManager\Formats\EndNoteXmlFormat;

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
            return $manager;
        });

        $this->app->singleton(ReferenceImporter::class);
        $this->app->singleton(ReferenceExporter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/refmanager.php' => config_path('refmanager.php'),
            ], 'refmanager-config');

            $this->commands([
                Commands\ImportCommand::class,
                Commands\ExportCommand::class,
                Commands\DuplicatesCommand::class,
                Commands\FormatsCommand::class,
            ]);
        }
    }
}
