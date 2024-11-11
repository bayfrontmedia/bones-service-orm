<?php

namespace Bayfront\BonesService\Orm\Traits;

/**
 * Filterable model.
 */
trait Filterable
{

    /**
     * Filter fields on create.
     *
     * When used with a ResourceModel, the filter will be applied after all other processing has been completed,
     * and just before the resource is created.
     *
     * @param array $fields
     * @return array
     */
    protected function filterOnCreate(array $fields): array
    {
        return $fields;
    }

    /**
     * Filter fields on read.
     *
     * When used with a ResourceModel, the filter will be applied after all other processing has been completed,
     * and just before the resource is returned.
     *
     * @param array $fields
     * @return array
     */
    protected function filterOnRead(array $fields): array
    {
        return $fields;
    }

    /**
     * Filter fields on update.
     *
     * When used with a ResourceModel, the filter will be applied after all other processing has been completed,
     * and just before the resource is updated.
     *
     * @param mixed $primary_key_id
     * @param array $fields
     * @return array
     * @noinspection PhpUnusedParameterInspection
     */
    protected function filterOnUpdate(mixed $primary_key_id, array $fields): array
    {
        return $fields;
    }

    /**
     * Filter fields.
     *
     * A generic method which can be used to filter fields.
     *
     * @param array $fields
     * @return array
     */
    protected function filter(array $fields): array
    {
        return $fields;
    }

}