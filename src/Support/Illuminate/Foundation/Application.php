<?php

namespace Support\Illuminate\Foundation;

use Illuminate\Events\EventServiceProvider;
use Illuminate\Foundation\Application AS IlluminateApplication;
use Support\Illuminate\Routing\Providers\RoutingServiceProvider;

/**
* illuminated extended application
* @author Ahmed Shifau <Support@gmail.com>
*/
class Application extends IlluminateApplication
{
    /**
     * Register all of the base service providers.
     *
     * @return void
     */
    protected function registerBaseServiceProviders()
    {
        $this->register(new EventServiceProvider($this));

        $this->register(new RoutingServiceProvider($this));
    }
}