<?php

namespace Bayfront\BonesService\Orm\Traits;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\BonesService\Orm\Exceptions\DoesNotExistException;
use Bayfront\BonesService\Orm\Exceptions\InvalidTraitException;
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
     * Filter next query to include soft-deleted resources.
     *
     * NOTE: This method has no effect on methods within another trait.
     *
     * @return static
     * @throws InvalidTraitException
     */
    public function withTrashed(): static
    {

        if (!$this instanceof ResourceModel) {
            throw new InvalidTraitException('Unable to query resource: Model must be instance of ResourceModel');
        }

        $this->with_trashed = true;
        return $this;
    }

    /**
     * Filter next query to include only soft-deleted resources.
     *
     * NOTE: This method has no effect on methods within another trait.
     *
     * @return static
     * @throws InvalidTraitException
     */
    public function onlyTrashed(): static
    {

        if (!$this instanceof ResourceModel) {
            throw new InvalidTraitException('Unable to query resource: Model must be instance of ResourceModel');
        }

        $this->only_trashed = true;
        return $this;
    }

    /**
     * Restore soft-deleted resource.
     *
     * If successful, the orm.resource.restore event is executed.
     *
     * @param mixed $primary_key_id
     * @return OrmResource|null
     * @throws InvalidTraitException
     * @throws UnexpectedException
     */
    public function restore(mixed $primary_key_id): ?OrmResource
    {

        if (!$this instanceof ResourceModel) {
            throw new InvalidTraitException('Unable to restore resource: Model must be instance of ResourceModel');
        }

        $deleted_at_field = $this->getDeletedAtField();

        /*
         * Must bypass the field validations used in the update() method
         */

        $this->ormService->db->query('UPDATE ' . $this->table_name . ' SET ' . $deleted_at_field . ' = NULL WHERE ' . $this->primary_key . ' = :primaryKey', [
            'primaryKey' => $primary_key_id
        ]);

        if ($this->ormService->db->rowCount() > 0) {

            try {
                $resource = $this->find($primary_key_id);
            } catch (DoesNotExistException) {
                return null;
            }

            $this->ormService->events->doEvent('orm.resource.restore', $resource);

            return $resource;
        }

        return null;

    }

    /**
     * Delete a single existing or soft-deleted resource.
     *
     * If successful, the orm.resource.delete event is executed.
     *
     * @param mixed $primary_key_id
     * @return bool
     * @throws InvalidTraitException
     * @throws UnexpectedException
     */
    public function hardDelete(mixed $primary_key_id): bool
    {

        if (!$this instanceof ResourceModel) {
            throw new InvalidTraitException('Unable to hard delete resource: Model must be instance of ResourceModel');
        }

        $this->with_trashed = true;

        try {
            $resource = $this->find($primary_key_id);
        } catch (DoesNotExistException) {
            return false;
        }

        $deleted = $this->ormService->db->delete($this->table_name, [
            $this->primary_key => $primary_key_id
        ]);

        if ($deleted) { // Ensure deleted from db
            $this->ormService->events->doEvent('orm.resource.delete', $resource);
            return true;
        }

        return false;

    }

    /**
     * Hard delete all soft-deleted resources deleted before a given timestamp.
     *
     * The orm.resource.delete event is executed for each deleted resource.
     *
     * @param int $timestamp
     * @return void
     * @throws InvalidTraitException
     * @throws UnexpectedException
     */
    public function purgeTrashed(int $timestamp): void
    {

        if (!$this instanceof ResourceModel) {
            throw new InvalidTraitException('Unable to purge trashed resources: Model must be instance of ResourceModel');
        }

        $datetime = date('Y-m-d H:i:s', $timestamp);

        $query = $this->newQuery();

        try {

            $results = $query->table($this->table_name)->select([
                $this->primary_key
            ])->where($this->getDeletedAtField(), 'lt', $datetime)->get();

        } catch (QueryException) {
            throw new UnexpectedException('Unable to purge trashed resources: Invalid query operator');
        }

        if (!empty($results)) {
            $results = Arr::pluck($results, $this->primary_key);
        }

        foreach ($results as $result) {
            $this->hardDelete($result);
        }

    }

    /**
     * Quietly hard delete all soft-deleted resources deleted before a given timestamp.
     *
     * All deletes are performed in the same database query, so the orm.resource.delete event is not executed.
     *
     * @param int $timestamp
     * @return void
     * @throws InvalidTraitException
     */
    public function purgeTrashedQuietly(int $timestamp): void
    {

        if (!$this instanceof ResourceModel) {
            throw new InvalidTraitException('Unable to quietly purge trashed resources: Model must be instance of ResourceModel');
        }

        $deleted_at = $this->getDeletedAtField();

        $this->ormService->db->query("DELETE FROM $this->table_name WHERE $deleted_at < :date", [
            'date' => date('Y-m-d H:i:s', $timestamp)
        ]);

    }

}