<?php 

namespace Support\JsonApi\Core\Decoder;

class DocumentObject
{
    /**
     * @var ResourceObject|ResourceObject[]|null
     */
    private $data;

    /**
     * Constructor.
     *
     * @param ResourceObject|ResourceObject[]|null $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return ResourceObject|ResourceObject[]|null
     */
    public function getData()
    {
        return $this->data;
    }
}
