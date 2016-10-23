<?php 

namespace Support\JsonApi\Contracts\Core;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;


interface PagingStrategyInterface
{
    /**
     * @param LengthAwarePaginator $paginator
     * @param string               $currentUrl
     * @param bool                 $treatAsHref
     * @param array                $urlParameters
     *
     * @return PagedDataInterface
     */
    public function createPagedData(
        LengthAwarePaginator $paginator,
        $currentUrl,
        $treatAsHref,
        array $urlParameters = []
    );

    /**
     * @param bool $isAddLink
     *
     * @return $this
     */
    public function setAddLinkToFirst($isAddLink);

    /**
     * @param bool $isAddLink
     *
     * @return $this
     */
    public function setAddLinkToPrev($isAddLink);

    /**
     * @param bool $isAddLink
     *
     * @return $this
     */
    public function setAddLinkToNext($isAddLink);

    /**
     * @param bool $isAddLink
     *
     * @return $this
     */
    public function setAddLinkToLast($isAddLink);
}
