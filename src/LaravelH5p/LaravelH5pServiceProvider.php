<?php

namespace InHub\LaravelH5p;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use InHub\LaravelH5p\Commands\MigrationCommand;
use InHub\LaravelH5p\Commands\ResetCommand;
use InHub\LaravelH5p\Helpers\H5pHelper;

class LaravelH5pServiceProvider extends ServiceProvider
{
    protected bool $defer = false;

    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected array $listen = [
        'InHub\LaravelH5p\Events\H5pEvent' => [
            'InHub\LaravelH5p\Listeners\H5pNotification',
        ],
    ];

    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('LaravelH5p', function ($app) {
            return new LaravelH5p($app);
        });

        $this->app->bind('H5pHelper', function () {
            return new H5pHelper();
        });

        $this->app->singleton('command.laravel-h5p.migration', function ($app) {
            return new MigrationCommand();
        });

        $this->app->singleton('command.laravel-h5p.reset', function ($app) {
            return new ResetCommand();
        });

        $this->commands([
            'command.laravel-h5p.migration',
            'command.laravel-h5p.reset',
        ]);
    }

    /**
     * Bootstrap the application services.
     *
     * @param Router $router
     * @return void
     */
    public function boot(Router $router): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/laravel-h5p.php');

        // config
        $this->publishes([
            __DIR__.'/../../config/laravel-h5p.php' => config_path('laravel-h5p.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../../config/laravel-h5p.php', 'laravel-h5p'
        );

        // language
        $this->publishes([
            __DIR__.'/../../lang/en/laravel-h5p.php' => resource_path('lang/en/laravel-h5p.php'),
        ], 'language');
        $this->publishes([
            __DIR__.'/../../lang/fr/laravel-h5p.php' => resource_path('lang/fr/laravel-h5p.php'),
        ], 'language');
        $this->publishes([
            __DIR__.'/../../lang/ar/laravel-h5p.php' => resource_path('lang/ar/laravel-h5p.php'),
        ], 'language');
        $this->publishes([
            __DIR__.'/../../lang/pt/laravel-h5p.php' => resource_path('lang/pt/laravel-h5p.php'),
        ], 'language');

        // views
        $this->publishes([
            __DIR__.'/../../views/h5p' => resource_path('views/h5p'),
        ], 'resources');

        // migrations
        $this->publishes([
            __DIR__.'/../../migrations/' => database_path('migrations'),
        ], 'migrations');

        // h5p
        $this->publishes([
            __DIR__.'/../../assets'                        => public_path('assets/vendor/laravel-h5p'),
            app_path('/../vendor/h5p/h5p-core/fonts')      => public_path('assets/vendor/h5p/h5p-core/fonts'),
            app_path('/../vendor/h5p/h5p-core/images')     => public_path('assets/vendor/h5p/h5p-core/images'),
            app_path('/../vendor/h5p/h5p-core/js')         => public_path('assets/vendor/h5p/h5p-core/js'),
            app_path('/../vendor/h5p/h5p-core/styles')     => public_path('assets/vendor/h5p/h5p-core/styles'),
            app_path('/../vendor/h5p/h5p-editor/ckeditor') => public_path('assets/vendor/h5p/h5p-editor/ckeditor'),
            app_path('/../vendor/h5p/h5p-editor/images')   => public_path('assets/vendor/h5p/h5p-editor/images'),
            app_path('/../vendor/h5p/h5p-editor/language') => public_path('assets/vendor/h5p/h5p-editor/language'),
            app_path('/../vendor/h5p/h5p-editor/libs')     => public_path('assets/vendor/h5p/h5p-editor/libs'),
            app_path('/../vendor/h5p/h5p-editor/scripts')  => public_path('assets/vendor/h5p/h5p-editor/scripts'),
            app_path('/../vendor/h5p/h5p-editor/styles')   => public_path('assets/vendor/h5p/h5p-editor/styles'),
        ], 'public');
    }

    public function provides(): array
    {
        return [
            'command.laravel-h5p.migration',
            'command.laravel-h5p.reset',
        ];
    }
}
