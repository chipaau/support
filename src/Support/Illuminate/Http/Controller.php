<?php

namespace Support\Illuminate\Http;

use Illuminate\Http\Response;
use Illuminate\Container\Container;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller AS IlluminateController;
use Support\Repositories\Repository;
use Support\JsonApi\Http\JsonApiRequest;
use Support\Validation\ValidateRequestTrait;
use Support\JsonApi\Contracts\Http\ResponsesInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

abstract class Controller extends IlluminateController
{
    use DispatchesJobs, ValidateRequestTrait;

    protected $container;
    protected $request;
    protected $repository;

    public function __construct(Container $container, Repository $repository, JsonApiRequest $request)
    {
        $this->container = $container;
        $this->repository = $repository;
        $this->request = $request;
    }

    protected function paginatedResponse($paginator)
    {
        $pagedData = $this->getFactory()
            ->createPagingStrategy()
            ->createPagedData(
                $paginator,
                $this->request->url(),
                true,
                $this->request->query()
            );
        return $this->getResponse()->getPagedDataResponse($pagedData);
    }

    protected function createdResponse($model)
    {
        return $this->getResponse()->getCreatedResponse($model);
    }

    protected function modelResponse($model)
    {
        return $this->getResponse()->getContentResponse($model);
    }

    protected function deletedResponse()
    {
        return $this->getResponse()->getCodeResponse(Response::HTTP_NO_CONTENT);
    }

    protected function getFactory()
    {
        return $this->request ? $this->request->getFactory() : null;
    }

    protected function getResponse()
    {
        return $this->container->make(ResponsesInterface::class);
    }

    public function getValidator()
    {
        return $this->container->make($this->request->validator());
    }

    public function validateRequest($removedReqired = false)
    {
        return $this->validate($this->getValidator(), $removedReqired);
    }
}
