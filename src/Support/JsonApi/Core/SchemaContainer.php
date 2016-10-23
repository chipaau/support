<?php 

namespace Support\JsonApi\Core;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaProviderInterface;
use Support\JsonApi\Contracts\Core\SchemaContainerInterface;

class SchemaContainer implements SchemaContainerInterface
{
    /**
     * @var SchemaFactoryInterface
     */
    private $factory;

    /**
     * @var array
     */
    private $type2modelMap = [];

    /**
     * @var array
     */
    private $model2typeMap = [];

    /**
     * @var array
     */
    private $model2schemaMap = [];

    /**
     * @var array
     */
    private $schema2ModelMap = [];

    /**
     * @var array
     */
    private $schemaInstances = [];

    /**
     * Constructor.
     *
     * @param SchemaFactoryInterface $factory
     * @param array                  $schemas
     */
    public function __construct(SchemaFactoryInterface $factory, array $schemas)
    {
        $this->factory = $factory;

        foreach ($schemas as $schemaClass) {
            if (empty($schemaClass) === true) {
                throw new InvalidArgumentException('Schema class is not specified.');
            }

            $typeName = $schemaClass::TYPE;
            if (empty($typeName) === true) {
                throw new InvalidArgumentException("Type is not set in Schema '$schemaClass'.");
            }
            if (array_key_exists($typeName, $this->type2modelMap) === true) {
                throw new InvalidArgumentException("Schema has been already registered for resource '$typeName'.");
            }

            $modelClass = $schemaClass::MODEL;
            if (empty($modelClass) === true) {
                throw new InvalidArgumentException("Model is not set in Schema '$schemaClass'.");
            }
            if (array_key_exists($modelClass, $this->type2modelMap) === true) {
                throw new InvalidArgumentException("Schema has been already registered for model '$modelClass'.");
            }

            $this->model2typeMap[$modelClass]    = $typeName;
            $this->type2modelMap[$typeName]      = $modelClass;
            $this->model2schemaMap[$modelClass]  = $schemaClass;
            $this->schema2ModelMap[$schemaClass] = $modelClass;
        }
    }

    /**
     * @inheritdoc
     */
    public function getSchema($model)
    {
        /** @var Model $model */
        $modelClass = $this->getModelClass($model);
        $result     = $this->getSchemaByType($modelClass);

        return $result;
    }

    /**
     * Get schema provider by resource type.
     *
     * @param string $modelClass
     *
     * @return SchemaProviderInterface
     */
    public function getSchemaByType($modelClass)
    {
        if (array_key_exists($modelClass, $this->schemaInstances) === true) {
            return $this->schemaInstances[$modelClass];
        }

        if ($this->hasSchemaForModelClass($modelClass) === false) {
            throw new InvalidArgumentException("Schema was not registered for model '$modelClass'.");
        }

        $schemaClass = $this->model2schemaMap[$modelClass];
        $schema      = new $schemaClass($this->factory, $this);

        $this->schemaInstances[$modelClass] = $schema;

        return $schema;
    }

    /**
     * @inheritdoc
     */
    public function getSchemaByResourceType($resourceType)
    {
        $modelClass = $this->getType($resourceType);
        $result     = $this->getSchemaByType($modelClass);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getSchemaBySchemaClass($schemaClass)
    {
        $modelClass = $this->getTypeBySchema($schemaClass);
        $result     = $this->getSchemaByType($modelClass);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getType($resourceType)
    {
        if ($this->hasSchemaForResourceType($resourceType) === false) {
            throw new InvalidArgumentException("Schema was not registered for resource '$resourceType'.");
        }

        $result = $this->type2modelMap[$resourceType];

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function hasSchemaForResourceType($resourceType)
    {
        $result = array_key_exists($resourceType, $this->type2modelMap);

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function hasSchemaForModelClass($modelClass)
    {
        $result = array_key_exists($modelClass, $this->model2schemaMap);

        return $result;
    }

    /**
     * @inheritdoc
     */
    private function getTypeBySchema($schemaClass)
    {
        if (array_key_exists($schemaClass, $this->schema2ModelMap) === false) {
            throw new InvalidArgumentException("Schema '$schemaClass' was not registered.");
        }

        $result = $this->schema2ModelMap[$schemaClass];

        return $result;
    }

    /**
     * @param Model $model
     *
     * @return string
     */
    private function getModelClass(Model $model)
    {
        return get_class($model);
    }
}
