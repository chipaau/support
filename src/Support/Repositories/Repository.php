<?php

namespace Support\Repositories;

use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\Builder;
use Support\Illuminate\Database\Eloquent\Model;
use Support\Repositories\RepositoryInterface;
use Support\Repositories\RepositoryException;
use Support\JsonApi\Errors\ErrorCollection;
use Neomerx\JsonApi\I18n\Translator as T;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface as QP;

/**
* Base repository
*/
abstract class Repository implements RepositoryInterface
{
    const PARAM_PAGING_SIZE = 'size';
    const PARAM_PAGING_NUMBER = 'number';
    const DEFAULT_PAGE_SIZE = 15;
    const MAX_PAGE_SIZE = 30;

    protected $container;
    protected $model;
    protected $parameters;
    protected $query;
    protected $resourceId = null;
    protected $relation = null;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function getCollection(EncodingParametersInterface $parameters, callable $callback = null)
    {
        $query = $this->with($parameters);
        if ($callback) {
            $query = $callback($query);
        }
        
        return $query->get($this->getFieldSets($parameters));
    }

    protected function with(EncodingParametersInterface $parameters = null, Builder $builder = null)
    {
        $with = (!is_null($parameters) && $parameters->getIncludePaths()) ? $parameters->getIncludePaths() : array();
        if (is_null($this->relation) ) {
            return  (is_null($builder)) ? $this->getModel()->with($with) : $builder->with($with);
        } else {
            if (is_null($builder)) {
                return $this->getModel()->{$this->relation}()->with($with)->getQuery();
            } else {
             $builder->with($with);
            }
        }
    }

    protected function getFilteringColumns(array $filtering = array())
    {
        //get the available filtering columns to be filtered
        //filtering columns for parent will get getFiltering
        //for the relationship filtering it will be get{relation}Filtring
        //
        
        if ($this->relation) {
            $method = 'get' . ucfirst($this->relation) . 'Filtring';
            $availableFilterings = call_user_func_array(array($this, $method), array());
        } else {
            $availableFilterings =  is_array($this->getFiltering()) ? $this->getFiltering() : array();
        }

        $array = array_diff(array_keys($filtering), array_keys($availableFilterings));
        if (!empty($array)) {
            throw new RepositoryException('\'' . implode(',', $array) . '\' is not available for the filtering.');
            
        }
        
        return $availableFilterings;
        
    }

    public function getPaginatedCollection(EncodingParametersInterface $parameters, callable $callback = null)
    {
        $query = $this->with($parameters);
        if ($callback) {
            $query = $callback($query);
        }

        $filtering = $parameters->getFilteringParameters();
        if (!empty($filtering)) {
            $filteringColumns = $this->getFilteringColumns($filtering);
            if (isset($filteringColumns['query'])) {
                $query = $this->buildSelectForFullText($query, $filtering, $filteringColumns);
            }
            foreach ($filtering as $key => $term) {
                $columns = array_get($filteringColumns, $key);
                $columns = $this->getColumnNames($columns, $key);
                $searchTerm = trim($term);
                if($key === 'query') {
                    $query = $this->filterByColumns($query, $columns, $searchTerm, 'LIKE');
                } else {
                    $column = array_shift($columns);
                    $query = $this->filterByColumn($query, $column, (strpos($searchTerm, '-') === 0 ? '!=':'='), $searchTerm, 'AND');
                }
                
            }
        }
        
        return $this->paginateBuilder($query, $parameters);
    }

    protected function getColumnNames($columns, $key)
    {
        if (is_null($columns)) {
            throw new FilteringException("Provided filter term '{$key}' can not  be found in filtering parameters.");
        }
        return (is_array($columns) ? $columns : explode(' ', $columns));
    }

    protected function filterByColumns($query, array $columns, $term, $operation = '=')
    {
        $table = null;
        $term = strtolower($operation) === 'like' ? '%'.$term.'%' : $term;
        if(method_exists($query, 'getTable')) {
            $table = $query->getTable();
        }
        return $query->where(function($query) use ($term, $columns, $term, $operation, $table) {
            foreach ($columns as $column) {
                if (strpos($column, 'pivot.') === 0) {
                    $column = $table .'.'. substr($column, 6);
                }
                $query = $this->filterByColumn($query, $column, $operation, $term, 'OR', strpos($column, 'fulltext.') === 0);
            }
            return $query;
        });
    }

    protected function filterByColumn($query, $column, $operation, $term, $andOr = 'OR', $fulltext = false)
    {
        if(isset($query->getModel()->casts) && isset($query->getModel()->casts[$column])) {
            $term = $this->castAttribute($query->getModel()->casts[$column], $term);
        }
        if ($fulltext) {
            $column = substr($column, 9);
            $query->orWhereRaw("MATCH($column) AGAINST(?)", array($term));
        }
        return $this->buildWhereClause($query, $column, $operation, $term, $andOr);
    }

    /**
     * Cast an attribute to a native PHP type.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        if (is_null($value)) {
            return $value;
        }
        $key = strtolower($key);
        switch ($key) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            default:
                return $value;
        }
    }

    protected function buildWhereClause($query, $column, $operation, $term, $andOr = 'OR')
    {
        $negate = false;
        if (strpos($term, '-') === 0) {
            $negate = true;
            $term = trim($term, '-');
        }
        if ($negate) {
            if ($term === '\0') { // this mean treat is as a null
                $query->whereNotNull($column);
            } elseif (str_contains($term, ',')) {
                $query->whereNotIn($column, explode(',', $term));
            } else {
                $query = $this->buildSimpleWhere($query, $column, $operation, $term, $andOr);    
            }
        } else {
            if ($term === '\0') { // this mean treat is as a null
                $query->whereNull($column);
            } elseif (str_contains($term, ',')) {
                $query->whereIn($column, explode(',', $term));
            } else {
                $query = $this->buildSimpleWhere($query, $column, $operation, $term, $andOr); 
            }
        }
        return $query;
    }

    protected function buildSimpleWhere($query, $column, $operation, $term, $andOr = 'OR')
    {
        return $query->where($column, $operation, $term, $andOr);
    }

    protected function buildSelectForFullText($query, $filtering, $filteringColumns)
    {
        if (!isset($filtering['query'])) {
            return $query;
        }
        if(! is_array($filteringColumns['query'])) {
            $filteringColumns['query'] = explode(' ', $filteringColumns['query']);
        }
        $relevence = array_where($filteringColumns['query'], function($key, $value) {
            return strpos($value, 'fulltext.') === 0;
        });

        if (empty($relevence)) {
            return $query;
        }
        
        $searchTerm = trim($filtering['query']);
        $column = array_shift($relevence);
        $column = substr($column, 9);
        return $query->selectRaw('*, MATCH('. $column .') AGAINST("'. $searchTerm .'") AS relevance');
    }

    /**
     * @param Builder                     $builder
     * @param EncodingParametersInterface $parameters
     *
     * @return PagedDataInterface
     */
    protected function paginateBuilder(Builder $builder, EncodingParametersInterface $parameters)
    {
        return $builder->paginate($this->getPageSize($parameters), $this->getFieldSets($parameters), 'page', $this->getPageNumber($parameters));
    }

    public function getItem($id, EncodingParametersInterface $parameters = null, callable $callback = null)
    {
        return $this->with($parameters)->findOrFail($id);
    }

    public function create(array $data = array())
    {
        $relation = $this->relation;

        if (!$relation) {
            return $this->model->create($data);
        }
        $relation = $this->model->{$relation}();

        if (method_exists($relation, 'withPivot')) {
            //get the key
            $otherkey = explode('.', $relation->getOtherKey());
            $key = array_pop($otherkey);
            if (!array_key_exists($key, $data)) {
                throw new \Exception("The otherkey value does not match the pivot relation otherkey. Please check the keys at validator.");
            }
            $keyId = array_pull($data, $key);
            $relation->attach($keyId, $data);
            $model = $relation->where($key, $keyId)->first();
        } else {
            $relationModel = $relation->getRelated()->fill($data);
            if (method_exists($relation, 'save')) {
                $model = $relation->save($relationModel);
            } else {
                $relationModel->save();
                $relation->associate($relationModel->id);
                $relation->save();
                $model = $relation->get();
            }
        }

        return $model;
    }

    public function update($id, array $data = array(), callable $callback = null)
    {
        $relation = $this->relation;
        if (!$relation) {
            $model = $this->getItem($id);
            $model->fill($data);
            if ($model->isDirty()) {
                $model->save();
            }

            return $model;
        }

        $relation = $this->model->{$relation}();
        $model = $relation->find($id);
        if (method_exists($relation, 'withPivot')) {
            $model->pivot->fill($data);
            if ($model->pivot->isDirty()) {
                $model->pivot->save();
            }

        } else {
            $model->fill($data);
            if ($model->isDirty()) {
                $model->save();
            }
        }

        return $model;
    }

    public function delete($id, callable $callback = null)
    {
        $relation = $this->relation;
        if (!$relation) {
            $model = $this->getItem($id);
            return $model->delete();
        }

        $model = $this->model->{$relation}();
        if (method_exists($model, 'withPivot')) {
            return $model->detach($id);
        } else {
            return $model->findOrFail($id)->delete();
        }
    }

    public function getModel()
    {
        return $this->model;
    }

        /**
     * @param EncodingParametersInterface|null $parameters
     *
     * @return int
     */
    protected function getPageSize(EncodingParametersInterface $parameters)
    {
        $paging = $parameters->getPaginationParameters();
        $size = isset($paging[self::PARAM_PAGING_SIZE]) ? $paging[self::PARAM_PAGING_SIZE] : self::DEFAULT_PAGE_SIZE;
        if($size > self::MAX_PAGE_SIZE) {
            $errors = $this->getErrorCollection();
            $errors->addQueryParameterError(QP::PARAM_PAGE, T::t(
            'Page size more then ' . self::MAX_PAGE_SIZE . ' is not allowed'));
            $this->throwException($errors);     
        }
        return $size;
    }

    protected function getErrorCollection()
    {
        return new ErrorCollection();
    }

    /**
     * @param EncodingParametersInterface|null $parameters
     *
     * @return int|null
     */
    protected function getPageNumber(EncodingParametersInterface $parameters)
    {
        $paging = $parameters->getPaginationParameters();
        return isset($paging[self::PARAM_PAGING_NUMBER]) ? $paging[self::PARAM_PAGING_NUMBER] : null;
    }

    protected function getFieldSets(EncodingParametersInterface $parameters)
    {
        return $parameters->getFieldSets() ?: array('*');
    }

    protected function throwException(ErrorCollection $errors, $status = Response::HTTP_BAD_REQUEST)
    {
        throw new JsonApiException($errors, $status);    
    }

    public function setRelation($relation)
    {
        $this->relation = $relation;
        return $this;
    }

    public function setResource($resourceId)
    {
        $this->resourceId = $resourceId;
        $this->model = $this->model->newQuery()->findOrFail($resourceId);
        return $this;
    }

    public function findBy(array $conditions, array $with = array())
    {
        return $this->model->with($with)->where($conditions)->firstOrFail();
    }
}