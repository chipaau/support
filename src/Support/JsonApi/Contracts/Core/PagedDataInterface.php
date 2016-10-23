<?php 

namespace Support\JsonApi\Contracts\Core;

use Neomerx\JsonApi\Contracts\Document\LinkInterface;

interface PagedDataInterface
{
    /**
     * @return array
     */
    public function getData();

    /**
     * @return LinkInterface[]
     */
    public function getLinks();

    /**
     * @return mixed
     */
    public function getMeta();
}
