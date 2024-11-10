<?php

namespace Bayfront\BonesService\Orm\Traits;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\BonesService\Orm\Exceptions\InvalidTraitException;
use Bayfront\BonesService\Orm\Exceptions\UnexpectedException;
use Bayfront\BonesService\Orm\Models\ResourceModel;
use Bayfront\SimplePdo\Exceptions\QueryException;

/**
 * Prune resources.
 */
trait Prunable
{

    /**
     * Datetime field used to prune resources.
     *
     * @return string
     */
    abstract protected function getPruneField(): string;

    /**
     * Delete resources older than timestamp.
     *
     * NOTE: Since this method utilizes the model's delete() method, models with SoftDeletes trait
     * will be soft-deleted.
     *
     * @param int $timestamp
     * @return void
     * @throws InvalidTraitException
     * @throws UnexpectedException
     */
    public function prune(int $timestamp): void
    {

        if (!$this instanceof ResourceModel) {
            throw new InvalidTraitException('Unable to prune resource: Model must be instance of ResourceModel');
        }

        $datetime = date('Y-m-d H:i:s', $timestamp);

        $query = $this->newQuery();

        try {

            $results = $query->table($this->table_name)->select([
                $this->primary_key
            ])->where($this->getPruneField(), 'lt', $datetime)->get();

        } catch (QueryException) {
            throw new UnexpectedException('Unable to prune resource: Invalid query operator');
        }

        if (!empty($results)) {
            $results = Arr::pluck($results, $this->primary_key);
        }

        foreach ($results as $result) {
            $this->delete($result);
        }

    }

    /**
     * Quietly hard delete resources older than timestamp.
     *
     * All deletes are performed in the same database query, so the orm.resource.delete event is not executed.
     *
     * @param int $timestamp
     * @return void
     * @throws InvalidTraitException
     */
    public function pruneQuietly(int $timestamp): void
    {

        if (!$this instanceof ResourceModel) {
            throw new InvalidTraitException('Unable to prune resource: Model must be instance of ResourceModel');
        }

        $prune_field = $this->getPruneField();

        $this->ormService->db->query("DELETE FROM $this->table_name WHERE $prune_field < :date", [
            'date' => date('Y-m-d H:i:s', $timestamp)
        ]);

    }

}