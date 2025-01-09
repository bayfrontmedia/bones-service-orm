<?php

namespace Bayfront\BonesService\Orm\Traits;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\BonesService\Orm\Exceptions\DoesNotExistException;
use Bayfront\BonesService\Orm\Exceptions\UnexpectedException;
use Bayfront\BonesService\Orm\Models\ResourceModel;
use Bayfront\BonesService\Orm\OrmResource;
use Bayfront\SimplePdo\Exceptions\QueryException;

/**
 * Soft-deletable resources.
 */
trait SoftDeletes
{

    /**
     * Datetime field used to save the deleted at date.
     *
     * @return string
     */
    abstract protected function getDeletedAtField(): string;

    protected bool $with_trashed = false;
    protected bool $only_trashed = false;

    /**
     * Reset trashed filters to default values.
     *
     * @return void
     */
    protected function resetTrashedFilters(): void
    {
        $this->with_trashed = false;
        $this->only_trashed = false;
    }

    /**
     * Filter the next query to include soft-deleted resources.
     *
     * NOTE: This method has no effect on methods within another trait.
     *
     * @return static
     * @throws UnexpectedException
     */
    public function withTrashed(): static
    {

        if (!$this instanceof ResourceModel) {
            throw new UnexpectedException('Unable to query resource: Model must be instance of ResourceModel');
        }

        $this->with_trashed = true;
        return $this;
    }

    /**
     * Filter the next query to include only soft-deleted resources.
     *
     * NOTE: This method has no effect on methods within another trait.
     *
     * @return static
     * @throws UnexpectedException
     */
    public function onlyTrashed(): static
    {

        if (!$this instanceof ResourceModel) {
            throw new UnexpectedException('Unable to query resource: Model must be instance of ResourceModel');
        }

        $this->only_trashed = true;
        return $this;
    }

    /**
     * Restore soft-deleted resource.
     *
     * If successful, the orm.resource.restored event is executed.
     *
     * @param mixed $primary_key_id
     * @return OrmResource
     * @throws DoesNotExistException
     * @throws UnexpectedException
     */
    public function restore(mixed $primary_key_id): OrmResource
    {

        if (!$this instanceof ResourceModel) {
            throw new UnexpectedException('Unable to restore resource: Model must be instance of ResourceModel');
        }

        $this->doBegin(__FUNCTION__);

        /*
         * Include soft-deleted resources in the next query.
         * This gets reset with the read() method.
         */

        $this->with_trashed = true;

        $previous = $this->find($primary_key_id);

        $this->onRestoring($previous);

        $deleted_at_field = $this->getDeletedAtField();

        /*
         * Must bypass the field validations used in the update() method
         * in order to modify the $deleted_at_field.
         */

        $this->ormService->db->query('UPDATE ' . $this->table_name . ' SET ' . $deleted_at_field . ' = NULL WHERE ' . $this->primary_key . ' = :primaryKey', [
            'primaryKey' => $primary_key_id
        ]);

        if ($this->ormService->db->rowCount() > 0) {

            try {
                $resource = $this->find($primary_key_id);
            } catch (DoesNotExistException) {
                throw new UnexpectedException('Unable to restore resource: Restored resource not found');
            }

            $this->onRestored($resource, $previous);
            $this->ormService->events->doEvent('orm.resource.restored', $resource, $previous);
            $this->doComplete(__FUNCTION__);
            return $resource;
        }

        throw new UnexpectedException('Unable to restore resource: Resource not updated');

    }

    /**
     * Delete a single existing or soft-deleted resource.
     *
     * If successful, the orm.resource.deleted event is executed.
     *
     * @param mixed $primary_key_id
     * @return bool
     * @throws UnexpectedException
     */
    public function hardDelete(mixed $primary_key_id): bool
    {

        if (!$this instanceof ResourceModel) {
            throw new UnexpectedException('Unable to hard-delete resource: Model must be instance of ResourceModel');
        }

        $this->doBegin(__FUNCTION__);

        /*
         * Include soft-deleted resources in the next query.
         * This gets reset with the read() method.
         */

        $this->with_trashed = true;

        try {
            $resource = $this->find($primary_key_id);
        } catch (DoesNotExistException) {
            $this->doComplete(__FUNCTION__);
            return false;
        }

        $this->onDeleting($resource);

        $deleted = $this->ormService->db->delete($this->table_name, [
            $this->primary_key => $primary_key_id
        ]);

        if ($deleted) { // Ensure deleted from db
            $this->onDeleted($resource);
            $this->ormService->events->doEvent('orm.resource.deleted', $resource);
            $this->doComplete(__FUNCTION__);
            return true;
        }

        $this->doComplete(__FUNCTION__);
        return false;

    }

    /**
     * Hard-delete all soft-deleted resources deleted before a given timestamp.
     *
     * Deleting events are executed for each deleted resource.
     *
     * @param int $timestamp
     * @return void
     * @throws UnexpectedException
     */
    public function purgeTrashed(int $timestamp): void
    {

        if (!$this instanceof ResourceModel) {
            throw new UnexpectedException('Unable to purge trashed resources: Model must be instance of ResourceModel');
        }

        $this->doBegin(__FUNCTION__);

        $datetime = date('Y-m-d H:i:s', $timestamp);

        $query = $this->newQuery();

        try {

            $query->table($this->table_name)->select([
                $this->primary_key
            ])->where($this->getDeletedAtField(), $query::OPERATOR_LESS_THAN, $datetime);

        } catch (QueryException) {
            throw new UnexpectedException('Unable to purge trashed resources: Invalid query operator');
        }

        $start_time = microtime(true);
        $results = $query->get();
        $this->ormService->db->setQueryTime($this->ormService->db->getCurrentConnectionName(), microtime(true) - $start_time);

        if (!empty($results)) {
            $results = Arr::pluck($results, $this->primary_key);
        }

        foreach ($results as $result) {
            $this->hardDelete($result);
        }

        $this->doComplete(__FUNCTION__);

    }

    /**
     * Quietly hard-delete all soft-deleted resources deleted before a given timestamp.
     *
     * All deletes are performed in the same database query, so deleting actions and events are not executed.
     *
     * @param int $timestamp
     * @return void
     * @throws UnexpectedException
     */
    public function purgeTrashedQuietly(int $timestamp): void
    {

        if (!$this instanceof ResourceModel) {
            throw new UnexpectedException('Unable to quietly purge trashed resources: Model must be instance of ResourceModel');
        }

        $this->doBegin(__FUNCTION__);

        $deleted_at = $this->getDeletedAtField();

        $this->ormService->db->query("DELETE FROM $this->table_name WHERE $deleted_at < :date", [
            'date' => date('Y-m-d H:i:s', $timestamp)
        ]);

        $this->doComplete(__FUNCTION__);

    }

    /*
     * |--------------------------------------------------------------------------
     * | Actions
     * |--------------------------------------------------------------------------
     */

    /**
     * Actions to perform after a resource has been soft-deleted.
     *
     * @param OrmResource $resource
     * @return void
     */
    protected function onTrashed(OrmResource $resource): void
    {

    }

    /**
     * Actions to perform before a resource is restored.
     *
     * @param OrmResource $resource
     * @return void
     */
    protected function onRestoring(OrmResource $resource): void
    {

    }

    /**
     * Actions to perform after a resource has been restored.
     *
     * @param OrmResource $resource (Newly updated resource)
     * @param OrmResource $previous (Previously existing resource)
     * @return void
     */
    protected function onRestored(OrmResource $resource, OrmResource $previous): void
    {

    }

}