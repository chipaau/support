<?php 

namespace Support\JsonApi\Core;

use Neomerx\JsonApi\Factories\Factory as BaseFactory;
use Support\JsonApi\Contracts\Core\FactoryInterface;

class Factory extends BaseFactory implements FactoryInterface
{
    /** @noinspection PhpMissingParentCallCommonInspection
     *
     * @inheritdoc
     */
    public function createContainer(array $providers = [])
    {
        return new SchemaContainer($this, $providers);
    }

    /**
     * @inheritdoc
     */
    public function createPagingStrategy($isAddFirst = true, $isAddPrev = true, $isAddNext = true, $isAddLast = true)
    {
        return new NumberAndSizePagingStrategy($this, $isAddFirst, $isAddPrev, $isAddNext, $isAddLast);
    }

    /**
     * @inheritdoc
     */
    public function createPagedData(array $data, array $links = [], $meta = null)
    {
        return new PagedData($data, $links, $meta);
    }
}
