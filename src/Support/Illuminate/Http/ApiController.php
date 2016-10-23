<?php

namespace Support\Illuminate\Http;

use Illuminate\Container\Container;
use Support\JsonApi\Http\JsonApiRequest;
use Support\Repositories\Repository;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

abstract class ApiController extends Controller implements ControllerInterface
{

    public function __construct(Container $container, Repository $repository, JsonApiRequest $request)
    {
        parent::__construct($container, $repository, $request);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $parameters = $this->request->getParameters();
        $paginator = $this->repository->getPaginatedCollection($parameters);
        return $this->paginatedResponse($paginator);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
        $parameters = $this->validateRequest();
        $model = $this->repository->create($parameters);
        return $this->createdResponse($model);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $parameters = $this->request->getParameters();
        $model = $this->repository->getItem($id, $parameters);
        return $this->modelResponse($model);
    }

    /**
     * Update the specified resource in storage.
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {
        $parameters = $this->validateRequest(true);
        $model = $this->repository->update($id, $parameters);
        return $this->modelResponse($model);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->repository->delete($id);
        return $this->deletedResponse();
    }
}
