<?php

namespace Translation;

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Facades\Config;
use Illuminate\Translation\TranslationServiceProvider as LaravelTranslationServiceProvider;
use Translation\Console\TranslationFindKeys;
use Translation\Console\TranslationFlush;
use Translation\Console\TranslationLoad;
use Translation\Contracts\TranslationContract;
use Translation\Drivers\Cache;
use Translation\Drivers\Database;
use Translation\Drivers\File;

class TranslationServiceProvider extends LaravelTranslationServiceProvider
{
    /**
     * The available commands.
     *
     * @var array
     */
    protected $commands = [
    TranslationFindKeys::class,
    TranslationFlush::class,
    TranslationLoad::class,
];

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishConfig();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();
        $this->registerBindings();
        $this->registerCommands();
        $this->registerMigrations();

        $this->app->singleton('translation.loader', fn () => $this->getLoader());
        $this->app->singleton('translator', function ($app) {
            $trans = new Translator(
                $app['translation.loader'],
                $app['config']['app.locale']
            );
            $trans->setFallback($app['config']['app.fallback_locale']);

            return $trans;
        });
    }

    /**
     * load migrations.
     *
     * @return void
     */
    public function registerMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }

    /**
     * publish module configuration file.
     *
     * @return void
     */
    public function publishConfig()
    {
        $this->publishes([
        __DIR__.'/../config/config.php' => config_path('translation.php'),
    ], 'config');
    }

    /**
     * bind interfaces to implementations.
     *
     * @return void
     */
    public function registerBindings()
    {
        foreach (config('translation.bindings') as $key => $binding) {
            $this->app->bind($key, $binding);
        }
    }

    /**
     * Get translation load.
     *
     * @return Loader
     */
    protected function getLoader(): Loader
    {
        $driver = Config::get('translation.default');

        $instance = null;
        switch ($driver) {
            case 'cache':
                $instance = resolve(Cache::class);
                break;
            case 'database':
                $instance = resolve(Database::class);
                break;
            default:
                $instance = new File(
                    resolve(TranslationContract::class),
                    $this->app['files'],
                    $this->app['path.lang']
                );
                break;
        }

        return $instance;
    }

    /**
     * Register commands.
     */
    protected function registerCommands()
    {
        $this->commands($this->commands);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/config.php',
            'translation'
        );
    }
}
