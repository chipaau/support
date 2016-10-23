<?php 

namespace Support\JsonApi\Core;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;
use Support\JsonApi\Contracts\Core\FactoryInterface;
use Support\JsonApi\Contracts\Core\PagingStrategyInterface;

class NumberAndSizePagingStrategy implements PagingStrategyInterface
{
    /**
     * Paging param name.
     */
    const PARAM_PAGING_SIZE = 'size';

    /**
     * Paging param name.
     */
    const PARAM_PAGING_NUMBER = 'number';

    /**
     * Paging param name.
     */
    const PARAM_PAGING_TOTAL_PAGES = 'total-pages';

    /**
     * Paging param name.
     */
    const PARAM_PAGING_TOTAL_ITEMS = 'total-items';

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var bool
     */
    private $isAddFirst;

    /**
     * @var bool
     */
    private $isAddPrev;

    /**
     * @var bool
     */
    private $isAddNext;

    /**
     * @var bool
     */
    private $isAddLast;

    /**
     * @var Closure|null
     */
    private $buildUrlClosure = null;

    /**
     * @param FactoryInterface $factory
     * @param bool             $isAddFirst
     * @param bool             $isAddPrev
     * @param bool             $isAddNext
     * @param bool             $isAddLast
     */
    public function __construct(
        FactoryInterface $factory,
        $isAddFirst = true,
        $isAddPrev = true,
        $isAddNext = true,
        $isAddLast = true
    ) {
        $this->factory    = $factory;
        $this->isAddFirst = $isAddFirst;
        $this->isAddPrev  = $isAddPrev;
        $this->isAddNext  = $isAddNext;
        $this->isAddLast  = $isAddLast;
    }

    /**
     * @inheritdoc
     */
    public function setAddLinkToFirst($isAddLink)
    {
        $this->isAddFirst = $isAddLink;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAddLinkToPrev($isAddLink)
    {
        $this->isAddPrev = $isAddLink;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAddLinkToNext($isAddLink)
    {
        $this->isAddNext = $isAddLink;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAddLinkToLast($isAddLink)
    {
        $this->isAddLast = $isAddLink;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function createPagedData(
        LengthAwarePaginator $paginator,
        $currentUrl,
        $treatAsHref,
        array $urlParameters = []
    ) {
        $currentPage = $paginator->currentPage();
        $totalPages  = $paginator->lastPage();

        $toFirstNeeded = $this->isAddFirst === true && $totalPages > 1;
        $toPrevNeeded  = $this->isAddPrev === true && $currentPage > 1;
        $toNextNeeded  = $this->isAddNext === true && $currentPage < $totalPages;
        $toLastNeeded  = $this->isAddLast === true && $totalPages > 1;

        $this->initBuildUrlClosure($paginator, $currentUrl, $urlParameters);

        $links = [];
        if ($toFirstNeeded === true) {
            $links[DocumentInterface::KEYWORD_FIRST] = $this->createLink(1, null, $treatAsHref);
        }

        if ($toPrevNeeded === true) {
            $links[DocumentInterface::KEYWORD_PREV] = $this->createLink($currentPage - 1, null, $treatAsHref);
        }

        if ($toNextNeeded === true) {
            $links[DocumentInterface::KEYWORD_NEXT] = $this->createLink($currentPage + 1, null, $treatAsHref);
        }

        if ($toLastNeeded === true) {
            $links[DocumentInterface::KEYWORD_LAST] = $this->createLink($totalPages, null, $treatAsHref);
        }

        $meta = $totalPages <= 1 ? null : [
            QueryParametersParserInterface::PARAM_PAGE => [
                self::PARAM_PAGING_SIZE        => (int) $paginator->perPage(),
                self::PARAM_PAGING_NUMBER      => (int) $currentPage,
                self::PARAM_PAGING_TOTAL_PAGES => (int) $totalPages,
                self::PARAM_PAGING_TOTAL_ITEMS => (int) $paginator->total(),
            ]
        ];

        $data      = $paginator->items();
        // dd(get_class_methods($this->factory), $this->factory);
        $pagedData = $this->factory->createPagedData($data, $links, $meta);

        return $pagedData;
    }

    /**
     * @param int   $pageNumber
     * @param mixed $meta
     * @param bool  $treatAsHref
     *
     * @return LinkInterface
     */
    protected function createLink($pageNumber, $meta, $treatAsHref)
    {
        $buildUrl = $this->buildUrlClosure;
        $link     = $this->factory->createLink($buildUrl($pageNumber), $meta, $treatAsHref);

        return $link;
    }

    /**
     * @param LengthAwarePaginator $paginator
     * @param string               $url
     * @param array                $parameters
     *
     * @return void
     */
    protected function initBuildUrlClosure(LengthAwarePaginator $paginator, $url, array $parameters = [])
    {
        if ($this->buildUrlClosure === null) {
            $pageSize   = $paginator->perPage();
            $urlLength  = strlen($url);
            $separator  = $urlLength > 0 && substr($url, -1) === '/' ? '?' : '/?';

            $this->buildUrlClosure = function ($pageNumber) use ($url, $parameters, $separator, $pageSize) {
                $paramsWithPaging = array_merge($parameters, [
                    QueryParametersParserInterface::PARAM_PAGE => [
                        self::PARAM_PAGING_SIZE   => $pageSize,
                        self::PARAM_PAGING_NUMBER => $pageNumber,
                    ]
                ]);
                $fullUrl = $url . $separator . http_build_query($paramsWithPaging);

                return $fullUrl;
            };
        }
    }
}
