<?php 

namespace Support\JsonApi\Core;

use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Support\JsonApi\Contracts\Core\PagedDataInterface;

class PagedData implements PagedDataInterface
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var LinkInterface[]
     */
    private $links;

    /**
     * @var mixed
     */
    private $meta;

    /**
     * PagedData constructor.
     *
     * @param array           $data
     * @param LinkInterface[] $links
     * @param mixed           $meta
     */
    public function __construct(array $data, array $links = [], $meta = null)
    {
        $this->data  = $data;
        $this->links = $links;
        $this->meta  = $meta;
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @inheritdoc
     */
    public function getLinks()
    {
        return $this->links;
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        return $this->meta;
    }
}
