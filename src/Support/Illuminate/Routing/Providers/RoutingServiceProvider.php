<?php

namespace Support\Illuminate\Routing\Providers;

use Support\Illuminate\Routing\Router;
use Illuminate\Routing\RoutingServiceProvider AS IlluminateRoutingServiceProvider;

/**
* extended service provider
* @author Ahmed Shifau <Support@gmail.com>
*/
class RoutingServiceProvider extends IlluminateRoutingServiceProvider
{
    /**
     * Register the router instance.
     *
     * @return void
     */
    protected function registerRouter()
    {
        $this->app['router'] = $this->app->share(function ($app) {
            return new Router($app['events'], $app);
        });
    }
}