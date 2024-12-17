<?php

namespace Bayfront\BonesService\Orm;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\BonesService\Orm\Exceptions\DoesNotExistException;
use Bayfront\BonesService\Orm\Exceptions\InvalidRequestException;
use Bayfront\BonesService\Orm\Exceptions\UnexpectedException;
use Bayfront\BonesService\Orm\Models\ResourceModel;

/**
 * Read-only single resource.
 */
class OrmResource
{

    private ResourceModel $resourceModel;
    private mixed $primary_key_id;
    private array $resource;

    /**
     * @param ResourceModel $resourceModel
     * @param mixed $primary_key_id
     * @throws DoesNotExistException
     * @throws UnexpectedException
     */
    public function __construct(ResourceModel $resourceModel, mixed $primary_key_id)
    {
        $this->resourceModel = $resourceModel;
        $this->primary_key_id = $primary_key_id;

        try {
            $this->resource = $resourceModel->read($primary_key_id);
        } catch (InvalidRequestException) {
            throw new UnexpectedException('Unable to construct resource: Error reading resource');
        }

    }

    /**
     * Get model instance of resource.
     *
     * @return ResourceModel
     */
    public function getModel(): ResourceModel
    {
        return $this->resourceModel;
    }

    /**
     * Get fully namespaced class of resource.
     *
     * @return string
     */
    public function getModelClassName(): string
    {
        return $this->resourceModel::class;
    }

    /**
     * Get primary key field value.
     *
     * @return mixed
     */
    public function getPrimaryKey(): mixed
    {
        return $this->primary_key_id;
    }

    /**
     * Get entire resource as an object.
     *
     * @return object
     */
    public function asObject(): object
    {
        return json_decode(json_encode($this->resource));
    }

    /**
     * Get entire resource as an array.
     *
     * @return array
     */
    public function read(): array
    {
        return $this->resource;
    }

    /**
     * Get resource array key in dot notation.
     *
     * @param string $key (Key to return in dot notation)
     * @param mixed $default (Default value to return)
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->resource, $key, $default);
    }

}