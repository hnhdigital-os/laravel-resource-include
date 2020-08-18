<?php

namespace HnhDigital\LaravelResoureInclude;

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

        $this->app->singleton('ResoureInclude', function () {
            return new ResoureInclude();
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

        blade::directive('captureScript', function ($name) {
            $name = empty($name) ? 'inline' : substr(str_replace('$', '', $name), 1, -1);

            return "<?php app('ResoureInclude')->add('js', ob_get_clean(), '".$name."', 'footer-inline'); ?>";
        });

        blade::directive('captureStyle', function ($name) {
            $name = empty($name) ? 'header' : substr(str_replace('$', '', $name), 1, -1);

            return "<?php app('ResoureInclude')->add('css', ob_get_clean(), '".$name."', 'footer-inline'); ?>";
        });

        blade::directive('resources', function ($name) {
            $name = trim($name, "'\"");
            $name = "'$name'";

            return "<?php app('ResoureInclude')->autoloadAssets(['js', 'css'], $name); ?>";
        });

        blade::directive('frontendAsset', function ($name) {
            $name = trim($name, "'\"");
            $name = "$name";

            return "<?= app('ResoureInclude')->$name(); ?>";
        });

        blade::directive('asset', function ($name) {
            if (strlen(trim($name, "'\"[]")) == strlen($name)) {
                $name = "'$name'";
            }

            return "<?php app('ResoureInclude')->package($name); ?>";
        });

        blade::directive('package', function ($name) {
            if (strlen(trim($name, "'\"[]")) == strlen($name)) {
                $name = "'$name'";
            }

            return "<?php app('ResoureInclude')->package($name); ?>";
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['ResoureInclude'];
    }
}
