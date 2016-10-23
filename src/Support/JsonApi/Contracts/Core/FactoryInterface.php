<?php 

namespace Support\JsonApi\Contracts\Core;

use Neomerx\JsonApi\Contracts\Factories\FactoryInterface AS NeomerxFactoryInterface;

interface FactoryInterface extends NeomerxFactoryInterface
{
    /**
     * @param array $data
     * @param array $links
     * @param mixed $meta
     *
     * @return PagedDataInterface
     */
    public function createPagedData(array $data, array $links = [], $meta = null);

    /**
     * @param bool $isAddFirst
     * @param bool $isAddPrev
     * @param bool $isAddNext
     * @param bool $isAddLast
     *
     * @return PagingStrategyInterface
     */
    public function createPagingStrategy($isAddFirst = true, $isAddPrev = true, $isAddNext = true, $isAddLast = true);
}
