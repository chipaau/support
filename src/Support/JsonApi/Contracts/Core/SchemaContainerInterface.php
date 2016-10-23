<?php 

namespace Support\JsonApi\Contracts\Core;

use Neomerx\JsonApi\Contracts\Schema\ContainerInterface as BaseContainerInterface;

interface SchemaContainerInterface extends BaseContainerInterface
{
    /**
     * Get schema provider for resource object.
     *
     * @param object $resourceObject
     *
     * @return SchemaInterface
     */
    public function getSchema($resourceObject);

    /**
     * Get schema provider by model class.
     *
     * @param string $modelClass
     *
     * @return SchemaInterface
     */
    public function getSchemaByType($modelClass);

    /**
     * Get schema provider by JSON API type.
     *
     * @param string $resourceType
     *
     * @return SchemaInterface
     */
    public function getSchemaByResourceType($resourceType);

    /**
     * Get schema provider by its type.
     *
     * @param string $schemaClass
     *
     * @return SchemaInterface
     */
    public function getSchemaBySchemaClass($schemaClass);

    /**
     * Get model type by JSON API type.
     *
     * @param string $resourceType
     *
     * @return string
     */
    public function getType($resourceType);

    /**
     * If container has a Schema for resource type.
     *
     * @param string $resourceType
     *
     * @return bool
     */
    public function hasSchemaForResourceType($resourceType);

    /**
     * If container has a Schema for model class.
     *
     * @param string $modelClass
     *
     * @return bool
     */
    public function hasSchemaForModelClass($modelClass);
}
