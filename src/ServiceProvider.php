<?php

namespace HnhDigital\LaravelResourceInclude;

use Blade;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.default.php', 'hnhdigital.resources');

        $this->app->singleton('ResourceInclude', function () {
            return new ResourceInclude();
        });
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('hnhdigital/resources.php'),
        ]);

        Blade::directive('captureScript', function ($name) {
            $name = empty($name) ? 'inline' : substr(str_replace('$', '', $name), 1, -1);

            return "<?php app('ResourceInclude')->add('js', ob_get_clean(), '".$name."', 'footer-inline'); ?>";
        });

        Blade::directive('captureStyle', function ($name) {
            $name = empty($name) ? 'header' : substr(str_replace('$', '', $name), 1, -1);

            return "<?php app('ResourceInclude')->add('css', ob_get_clean(), '".$name."', 'footer-inline'); ?>";
        });

        Blade::directive('resources', function ($name) {
            $name = trim($name, "'\"");
            $name = "'$name'";

            return "<?php app('ResourceInclude')->autoInclude(['js', 'css'], $name); ?>";
        });

        Blade::directive('resoureInclude', function ($name) {
            $name = trim($name, "'\"");
            $name = "$name";

            return "<?= app('ResourceInclude')->$name(); ?>";
        });

        Blade::directive('asset', function ($name) {
            if (strlen(trim($name, "'\"[]")) == strlen($name)) {
                $name = "'$name'";
            }

            return "<?php app('ResourceInclude')->package($name); ?>";
        });

        Blade::directive('package', function ($name) {
            if (strlen(trim($name, "'\"[]")) == strlen($name)) {
                $name = "'$name'";
            }

            return "<?php app('ResourceInclude')->package($name); ?>";
        });

        Blade::directive('resourcesFor', function ($name) {
            $name = trim($name, "'\"");
            $name = "$name";

            return "<?php app('ResourceInclude')->{$name}(); ?>";
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['ResourceInclude'];
    }
}
