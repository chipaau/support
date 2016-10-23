<?php

namespace Support\JsonApi\Exceptions\Providers;

use Illuminate\Http\Response;
use Illuminate\Config\Repository;
use Illuminate\Support\ServiceProvider;
use Support\JsonApi\Exceptions\RendererContainer;
use Support\JsonApi\Contracts\Http\ResponsesInterface;
use Support\JsonApi\Exceptions\Renderers\NoContentRenderer;
use Support\JsonApi\Contracts\Exceptions\RendererContainerInterface;

class ExceptionServiceProvider extends ServiceProvider
{

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfig();
        $this->app->singleton(RendererContainerInterface::class, function() {
            $default = $this->getDefaultRenderer();
            $container = new RendererContainer($default);
            $container->registerRenderers($this->getExceptionRenderers());
            return $container;
        });
    }

    protected function mergeConfig()
    {
        //get the exception config file
        $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'exceptions.php';
        //register the config
        $this->mergeConfigFrom($file, 'exceptions');
    }

    protected function getDefaultRenderer()
    {
        $default = $this->getConfig()['default'];
        return new $default($this->app[ResponsesInterface::class]);
    }

    protected function getExceptionRenderers()
    {
        $renderers = $this->getConfig()['list'];
        if (empty($renderers)) {
            return $renderers;
        }

        foreach ($renderers as $exception => $renderer) {
            $renderers[$exception] = new $renderer($this->app[ResponsesInterface::class]);
        }
        return $renderers;
    }

    protected function getConfig()
    {
        return $this->app[Repository::class]->get('exceptions');
    }

    protected function getResponse()
    {
        return $this->app->make(ResponsesInterface::class);
    }
}
