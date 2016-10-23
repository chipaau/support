<?php

namespace Support\JsonApi\Core\Decoder;

class ResourceObject extends ResourceIdentifierObject
{
    /**
     * @var array
     */
    private $attributes;

    /**
     * @var RelationshipsObject[]
     */
    private $relationships;

    /**
     * Constructor.
     *
     * @param string                $type
     * @param string                $identifier
     * @param array                 $attributes
     * @param RelationshipsObject[] $relationships
     */
    public function __construct($type, $identifier, array $attributes, array $relationships)
    {
        parent::__construct($type, $identifier);

        $this->attributes    = $attributes;
        $this->relationships = $relationships;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return RelationshipsObject[]
     */
    public function getRelationships()
    {
        return $this->relationships;
    }
}
