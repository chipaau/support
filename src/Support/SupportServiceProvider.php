<?php

namespace Support;

use Illuminate\Support\ServiceProvider;

class SupportServiceProvider extends ServiceProvider
{
    private $commandPath = 'support.';

    private $packagePath = 'Support\Console\Commands\\';

    protected $commands = [
        \Support\Console\Commands\MakeResource::class,
        \Support\Console\Commands\MakeResourceController::class,
        \Support\Console\Commands\MakeResourceRepository::class,
        \Support\Console\Commands\MakeResourceRequest::class,
        \Support\Console\Commands\MakeResourceRoute::class,
        \Support\Console\Commands\MakeResourceSchema::class,
        \Support\Console\Commands\MakeResourceValidator::class,
        \Support\Console\Commands\MakeResourceModel::class
    ];

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // $this->publishes([
        //     __DIR__.'/Console/config/config.php' => config_path('support.php'),
        // ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // merge config
        // $configPath = __DIR__ . '/Console/config/config.php';
        // $this->mergeConfigFrom($configPath, 'support');

        // register all the artisan commands
        $this->commands($this->commands);
    }
}