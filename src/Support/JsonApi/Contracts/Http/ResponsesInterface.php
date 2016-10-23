<?php 

namespace Support\JsonApi\Contracts\Http;

use Support\JsonApi\Contracts\Core\PagedDataInterface;
use Neomerx\JsonApi\Contracts\Http\ResponsesInterface AS NeomerxResponseInterface;


interface ResponsesInterface extends NeomerxResponseInterface
{
    /**
     * Get response for paged data.
     *
     * @param PagedDataInterface $data
     * @param int                $statusCode
     *
     * @return mixed
     */
    public function getPagedDataResponse(PagedDataInterface $data, $statusCode = self::HTTP_OK);
}
