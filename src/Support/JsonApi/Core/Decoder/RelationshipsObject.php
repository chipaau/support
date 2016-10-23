<?php 

namespace Support\JsonApi\Core\Decoder;

class RelationshipsObject
{
    /**
     * @var ResourceIdentifierObject[]|ResourceIdentifierObject|null|[]
     */
    private $data;

    /**
     * Constructor.
     *
     * @param ResourceIdentifierObject[]|ResourceIdentifierObject|null|[] $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return ResourceIdentifierObject|ResourceIdentifierObject[]|null|[]
     */
    public function getData()
    {
        return $this->data;
    }
}
