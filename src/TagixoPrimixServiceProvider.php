<?php

namespace Tagixo\Primix;

use Illuminate\Support\ServiceProvider;
use LiVue\Features\SupportAssets\AssetManager;
use LiVue\Features\SupportAssets\Css;

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
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'tagixo-primix');
        $this->loadRoutesFrom(__DIR__.'/../routes/tagixo-primix.php');
        $this->registerPanelStyles();

        $this->publishes([
            __DIR__.'/../config/tagixo-primix.php' => config_path('tagixo-primix.php'),
        ], 'tagixo-primix-config');
    }

    /**
     * The rules of the screens this SDK draws itself (the Theme Builder's zone
     * cards), inline on the LiVue app of the panel.
     *
     * Inline, and not a published file: there is barely a page of it, and a
     * <style> inside a component does not survive LiVue's morphing.
     */
    protected function registerPanelStyles(): void
    {
        $this->app->booted(function (): void {
            $css = @file_get_contents(__DIR__.'/../resources/css/panel.css');

            if (! is_string($css) || trim($css) === '') {
                return;
            }

            app(AssetManager::class)->register([
                Css::make('tagixo-primix-panel')->inline($css),
            ], 'tagixo-primix');
        });
    }
}
