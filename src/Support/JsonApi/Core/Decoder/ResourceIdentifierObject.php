<?php

namespace Support\JsonApi\Core\Decoder;

class ResourceIdentifierObject
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $identifier;

    /**
     * Constructor.
     *
     * @param string $type
     * @param string $identifier
     */
    public function __construct($type, $identifier)
    {
        $this->type       = $type;
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }
}
