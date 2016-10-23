<?php

namespace Support\Illuminate\Routing;

use Illuminate\Routing\Router AS IlluminateRouter;
use Support\Illuminate\Routing\ResourceRegistrar;

/**
* extended router for the Illuminate Router
*/
class Router extends IlluminateRouter
{

    /**
     * Route a resource to a controller.
     *
     * @param  string  $name
     * @param  string  $controller
     * @param  array   $options
     * @return void
     */
    public function resource($name, $controller, array $options = [])
    {
        if ($this->container && $this->container->bound(ResourceRegistrar::class)) {
            $registrar = $this->container->make(ResourceRegistrar::class);
        } else {
            $registrar = new ResourceRegistrar($this);
        }
        
        $registrar->register($name, $controller, $options);
    }
    
}