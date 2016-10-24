<?php

namespace Support\Illuminate\Database\Eloquent;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model AS EloquentModel;
use Support\Illuminate\Database\Eloquent\ModelInterface;


/**
* Base model for extending eloquent based function
*/
abstract class Model extends EloquentModel implements ModelInterface
{

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    
    public function __call($method, $parameters)
    {
        if (strpos($method, '-') !== false) {
            $method = Str::camel($method);
            return call_user_func_array([$this, $method], $parameters);
        }

        return parent::__call($method, $parameters);
    }

}