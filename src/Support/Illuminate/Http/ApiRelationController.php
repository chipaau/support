<?php

namespace Support\Illuminate\Http;

use Illuminate\Container\Container;
use Support\JsonApi\Http\JsonApiRequest;
use Support\Repositories\Repository;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

abstract class ApiRelationController extends Controller implements RelationControllerInterface
{
    public function __construct(Container $container, Repository $repository, JsonApiRequest $request)
    {
        parent::__construct($container, $repository, $request);
    }

    public abstract function relation();

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($resourceId)
    {
        $parameters = $this->request->getParameters();
        $paginator = $this->repository
        ->setResource($resourceId)
        ->setRelation($this->relation())
        ->getPaginatedCollection($parameters);
        return $this->paginatedResponse($paginator);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($resourceId, $childResouceId)
    {
        $parameters = $this->request->getParameters();
        $model = $this->repository
        ->setResource($resourceId)
        ->setRelation($this->relation())
        ->getItem($childResouceId, $parameters);
        return $this->modelResponse($model);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store($resourceId)
    {
        $parameters = $this->validateRequest();
        $model = $this->repository
        ->setResource($resourceId)
        ->setRelation($this->relation())
        ->create($parameters);
        return $this->createdResponse($model);
    }

    /**
     * Update the specified resource in storage.
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($resourceId, $childResouceId)
    {
        $parameters = $this->validateRequest(true);
        $model = $this->repository
        ->setResource($resourceId)
        ->setRelation($this->relation())
        ->update($childResouceId, $parameters);
        return $this->modelResponse($model);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($resourceId, $childResouceId)
    {
        $this->repository
        ->setResource($resourceId)
        ->setRelation($this->relation())
        ->delete($childResouceId);
        return $this->deletedResponse();
    }
}
