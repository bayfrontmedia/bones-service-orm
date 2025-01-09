<?php

namespace Bayfront\BonesService\Orm\Interfaces;

interface FieldParserInterface
{

    /**
     * Get fields to be returned. The actual field names must be used.
     *
     * @return array
     */
    public function getFields(): array;

}