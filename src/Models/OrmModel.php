<?php

namespace Bayfront\BonesService\Orm\Models;

use Bayfront\Bones\Abstracts\Model;
use Bayfront\BonesService\Orm\Exceptions\UnexpectedException;
use Bayfront\BonesService\Orm\OrmService;
use Bayfront\SimplePdo\Query;
use Bayfront\StringHelpers\Str;

abstract class OrmModel extends Model
{

    public OrmService $ormService;

    /**
     * @param OrmService $ormService
     */
    public function __construct(OrmService $ormService)
    {
        $this->ormService = $ormService;

        parent::__construct($this->ormService->events);

        $this->ormService->events->doEvent('orm.model', $this);

    }

    /**
     * Table name.
     *
     * @var string
     */
    protected string $table_name = '';

    /**
     * Get table name.
     *
     * @return string
     */
    public function getTableName(): string
    {
        return $this->table_name;
    }

    /**
     * Get new Query instance.
     *
     * See: https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md
     *
     * @return Query
     */
    public function newQuery(): Query
    {
        return new Query($this->ormService->db->getCurrentConnection());
    }

    /**
     * Create a lexicographically sortable UUID v7 string.
     *
     * @return string
     */
    public function createUuid(): string
    {
        return Str::uuid7();
    }

    /**
     * Transform fields according to a defined rule.
     *
     * @param array $resource (Array potentially containing fields to transform)
     * @param array $rules (Set of rules as $field => $callable)
     * @return array (Transformed array)
     * @throws UnexpectedException
     */
    protected function transform(array $resource, array $rules): array
    {

        foreach ($rules as $field => $callable) {

            if (!isset($resource[$field])) {
                continue;
            }

            if (!is_callable($callable)) {
                throw new UnexpectedException('Unable to transform fields: Rule not callable for field (' . $field . ')');
            }

            $resource[$field] = call_user_func($callable, $resource[$field]);

        }

        return $resource;

    }

}