<?php 

namespace Support\JsonApi\Contracts\Core;

use Neomerx\JsonApi\Contracts\Schema\SchemaProviderInterface;

interface SchemaInterface extends SchemaProviderInterface
{
    /** Type */
    const TYPE = null;

    /** Model class name */
    const MODEL = null;
}
