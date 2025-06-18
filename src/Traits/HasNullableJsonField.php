<?php

namespace Bayfront\BonesService\Orm\Traits;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\BonesService\Orm\Exceptions\InvalidFieldException;
use Bayfront\BonesService\Orm\OrmService;

/**
 * Has a JSON field whose keys are removed by passing a NULL value.
 *
 * Keys can only contain alphanumeric characters, underscores and dashes.
 */
trait HasNullableJsonField
{

    /**
     * Nullable JSON field.
     *
     * @return string
     */
    abstract protected function getNullableJsonField(): string;

    /**
     * Validate key.
     *
     * @param string $key
     * @return void
     * @throws InvalidFieldException
     */
    private function validateKey(string $key):  void
    {

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $key)) {
            throw new InvalidFieldException('Invalid ' . $this->getNullableJsonField() . ' key: Keys can only contain alphanumeric characters, underscores and dashes');
        }

    }

    /**
     * Define nullable JSON field array, removing keys with null values.
     *
     * @param array $array
     * @return array
     * @throws InvalidFieldException
     */
    protected function defineNullableJsonField(array $array): array
    {

        $array = Arr::dot($array);

        foreach ($array as $k => $v) {

            $this->validateKey($k);

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
     * @param string $json_field
     * @param array $array
     * @return array
     * @throws InvalidFieldException
     */
    protected function updateNullableJsonField(OrmService $ormService, string $table_name, string $primary_key_field, mixed $primary_key, string $json_field, array $array): array
    {

        $meta = $ormService->db->single("SELECT $json_field FROM $table_name WHERE $primary_key_field = :id", [
            'id' => $primary_key
        ]);

        if (is_string($meta)) {
            $meta_dot = json_decode($meta, true);
        } else {
            $meta_dot = [];
        }

        if (is_array($meta_dot)) { // Checks decode was successful
            $meta = array_merge(Arr::dot($meta_dot), Arr::dot($array));
        } else {
            $meta = Arr::dot($array);
        }

        foreach ($meta as $k => $v) {

            $this->validateKey($k);

            if ($v === null) {
                unset($meta[$k]);
            }

        }

        return Arr::undot($meta);

    }

}