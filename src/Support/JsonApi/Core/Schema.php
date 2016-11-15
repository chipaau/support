<?php 

namespace Support\JsonApi\Core;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use Neomerx\JsonApi\Schema\SchemaProvider;
use Support\JsonApi\Contracts\Core\FactoryInterface;
use Support\JsonApi\Contracts\Core\SchemaInterface;

abstract class Schema extends SchemaProvider implements SchemaInterface
{
    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var PagingStrategyInterface
     */
    private $pagingStrategy = false;

    /**
     * @var string
     */
    protected $carbonFormat = Carbon::ISO8601;

    /**
     * EloquentSchema constructor.
     *
     * @param FactoryInterface   $factory
     * @param ContainerInterface $container
     */
    public function __construct(FactoryInterface $factory, ContainerInterface $container)
    {
        $this->factory = $factory;
        $this->resourceType = static::TYPE;
        parent::__construct($factory, $container);
    }

    /**
     * @inheritdoc
     */
    final public function getId($model)
    {
        /** @var Model $model */
        return $model->getKey();
    }

    /**
     * @inheritdoc
     */
    public function getAttributes($model)
    {
        /** @var Model $model */
        $result = array();
        $mappings = call_user_func(array($model, 'mappings'));
        foreach ($model->attributesToArray() as $key => $value) {
            if($key == 'id') continue;
            if ($model->$key instanceof Carbon) {
                $value = $model->$key->format($this->carbonFormat);
            }
            if(isset($mappings[$key])) {
                $result[$mappings[$key]] = $value;
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    protected function changeKeyToDashed($key)
    {
        return Str::snake($key, '-');
    }

    /** @noinspection PhpMissingParentCallCommonInspection
     *
     * @inheritdoc
     */
    final public function getRelationships($model, $isPrimary, array $includeRelationships)
    {
        $includables = array();
        foreach ($includeRelationships as $key => $relation) {
            $relationship = $this->loadRelation($model, $relation, $includeRelationships);
            $includables[$this->changeKeyToDashed($key)] = [self::DATA => $relationship ];
        }
        return $includables;
    }

    protected function loadRelation($model, $relation, $includeRelationships)
    {
        if (isset($includeRelationships[$relation]) || isset($model->relationsToArray()[$relation])) {
            $relationship = $model->getRelation($relation);
        } else {
            $modelRelation = $model->$relation();
            $relationship = $modelRelation->getModel();
            $value = $modelRelation->getForeignKey();
            //if relation returns model, it means it can be loaded with the model without querying
            // else just ignore it
            if (method_exists($modelRelation, 'associate') && !is_null($model->$value)) {
                $relationship->setAttribute($relationship->getKeyName(), $model->$value);
            } else {
                return;
            }
        }

        return $relationship instanceof Collection ? $relationship->all() : $relationship;
    }
}
