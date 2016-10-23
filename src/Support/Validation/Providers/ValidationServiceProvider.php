<?php

namespace Support\Validation\Providers;

use Illuminate\Validation\Factory;
use Illuminate\Support\ServiceProvider;
use Support\Validation\Validator;
use Support\Validation\DatabasePresenceVerifier;

/**
 * service provider to register the extended validator
 * @author Ahmed Shifau <Support@gmail.com>
 */
class ValidationServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerPresenceVerifier();

        $this->app->bind('validator', function ($app) {
            $validator = new Factory($app['translator'], $app);

            // The validation presence verifier is responsible for determining the existence
            // of values in a given data collection, typically a relational database or
            // other persistent data stores. And it is used to check for uniqueness.
            if (isset($app['validation.presence'])) {
                $validator->setPresenceVerifier($app['validation.presence']);
            }

            $validator->resolver(function($translator, $data, $rules, $messages) use ($app)
            {
                return new Validator($translator, $data, $rules, $messages);
            });

            return $validator;
        });
    }

    /**
     * Register the database presence verifier.
     *
     * @return void
     */
    protected function registerPresenceVerifier()
    {
        $this->app->bind('validation.presence', function ($app) {
            return new DatabasePresenceVerifier($app['db']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('validator', 'validation.presence');
    }
}