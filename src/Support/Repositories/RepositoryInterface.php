<?php

namespace Support\Repositories;

use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

interface RepositoryInterface {
    public function getCollection(EncodingParametersInterface $parameters, callable $callback = null);
    public function getPaginatedCollection(EncodingParametersInterface $parameters, callable $callback = null);
    public function getItem($id, EncodingParametersInterface $parameters = null, callable $callback = null);
    public function create(array $data = array());
    public function update($id, array $data = array(), callable $callback = null);
    public function delete($id, callable $callback = null);
    public function setRelation($relation);
    public function setResource($resourceId);

    public static function getFiltering();
}