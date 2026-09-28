<?php

namespace Tagixo\Primix;

use Illuminate\Support\ServiceProvider;

class TagixoPrimixServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/tagixo-primix.php', 'tagixo-primix');

        // One instance: the panel configures the plugin fluently and the static
        // resources read that same configuration back.
        $this->app->singleton(TagixoPrimixPlugin::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/tagixo-primix.php' => config_path('tagixo-primix.php'),
        ], 'tagixo-primix-config');
    }
}
