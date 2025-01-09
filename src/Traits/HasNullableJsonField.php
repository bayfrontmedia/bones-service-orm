<?php

namespace Bayfront\BonesService\Orm\Traits;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\BonesService\Orm\OrmService;

/**
 * Has a JSON field whose keys are removed by passing a NULL value.
 */
trait HasNullableJsonField
{

    /**
     * Define nullable JSON field array, removing keys with null values.
     *
     * @param array $array
     * @return array
     */
    protected function defineNullableJsonField(array $array): array
    {

        $array = Arr::dot($array);

        foreach ($array as $k => $v) {
            if ($v === null) {
                unset($array[$k]);
            }
        }

        return Arr::undot($array);

    }

    /**
     * Update nullable JSON field array, merging updated array with existing and removing keys with null values.
     *
     * @param OrmService $ormService
     * @param string $table_name
     * @param string $primary_key_field
     * @param mixed $primary_key
     * @param array $array
     * @return array
     */
    protected function updateNullableJsonField(OrmService $ormService, string $table_name, string $primary_key_field, mixed $primary_key, array $array): array
    {

        $meta = $ormService->db->single("SELECT $primary_key_field FROM $table_name WHERE $primary_key_field = :id", [
            'id' => $primary_key
        ]);

        $meta = array_merge(Arr::dot(json_decode($meta, true)), Arr::dot($array));

        foreach ($meta as $k => $v) {
            if ($v === null) {
                unset($meta[$k]);
            }
        }

        return Arr::undot($meta);

    }

}