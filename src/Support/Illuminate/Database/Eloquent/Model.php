<?php

namespace Support\Illuminate\Database\Eloquent;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model AS EloquentModel;
use Support\Illuminate\Database\Eloquent\ModelInterface;
use Support\Illuminate\Database\Eloquent\Relations\HasManyThroughMany;


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

    /**
     * get relationships through another table
     * @param  string  $related    first ralation table
     * @param  string  $through    second relation table
     * @param  string  $firstKey   key of first table
     * @param  string  $secondKey  second key
     * @param  string  $localKey   local key
     * @param  string  $throughKey through key
     * @return HasManyThroughMany
     */
    public function hasManyThroughMany(
        $related,
        $through,
        $firstKey = null,
        $secondKey = null,
        $localKey = null,
        $throughKey = null
    )
    {
        $through = new $through;
        $firstKey = $firstKey ?: $this->getForeignKey();
        $secondKey = $secondKey ?: $through->getForeignKey();
        $localKey = $localKey ?: $this->getKeyName();
        return new HasManyThroughMany((new $related)->newQuery(), $this, $through, $firstKey, $secondKey, $localKey, $throughKey);
    }

}