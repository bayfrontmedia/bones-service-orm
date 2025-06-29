<?php

namespace Bayfront\BonesService\Orm\Traits;

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

        foreach ($array as $k => $v) {

            $this->validateKey($k);

            if ($v === null) {
                unset($array[$k]);
            }

        }

        ksort($array);

        return $array;

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
            $existing_meta = json_decode($meta, true);
        } else {
            $existing_meta = [];
        }

        if (is_array($existing_meta)) { // Checks decode was successful
            $meta = array_merge($existing_meta, $array);
        } else {
            $meta = $array;
        }

        foreach ($meta as $k => $v) {

            $this->validateKey($k);

            if ($v === null) {
                unset($meta[$k]);
            }

        }

        ksort($meta);

        return $meta;

    }

}