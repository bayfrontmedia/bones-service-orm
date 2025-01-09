<?php

namespace Bayfront\BonesService\Orm\Utilities\Parsers;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\BonesService\Orm\Interfaces\FieldParserInterface;

/**
 * Parse fields from an array
 */
class FieldParser implements FieldParserInterface
{

    private array $query;

    public function __construct(array $query)
    {
        $this->query = $query;
    }

    /**
     * Explode commas if a string.
     *
     * @param mixed $value
     * @return array
     */
    private function explodeCommas(mixed $value): array
    {
        if (is_string($value)) {
            return explode(',', $value);
        }
        return (array)$value;
    }

    /**
     * @inheritDoc
     */
    public function getFields(): array
    {
        return $this->explodeCommas(Arr::get($this->query, 'fields', []));
    }

}