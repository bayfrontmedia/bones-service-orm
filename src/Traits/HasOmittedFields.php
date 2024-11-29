<?php

namespace Bayfront\BonesService\Orm\Traits;

/**
 * Has fields containing potentially sensitive information
 * to omit from external processing (i.e. logging)
 */
trait HasOmittedFields
{

    /**
     * Get omitted fields.
     *
     * @return array
     */
    abstract public function getOmittedFields(): array;

    /**
     * Is field omitted?
     *
     * @param string $field
     * @return bool
     */
    public function isOmittedField(string $field): bool
    {
        return in_array($field, $this->getOmittedFields());
    }

}