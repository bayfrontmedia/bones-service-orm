<?php

namespace Bayfront\BonesService\Orm\Models;

use Bayfront\Bones\Abstracts\Model;
use Bayfront\BonesService\Orm\Exceptions\InvalidDatabaseNameException;
use Bayfront\BonesService\Orm\OrmService;
use Bayfront\SimplePdo\Exceptions\InvalidDatabaseException;
use Bayfront\SimplePdo\Query;
use Bayfront\StringHelpers\Str;

abstract class OrmModel extends Model
{

    public OrmService $ormService;

    /**
     * @param OrmService $ormService
     * @throws InvalidDatabaseNameException
     */
    public function __construct(OrmService $ormService)
    {
        $this->ormService = $ormService;

        if ($this->db_name !== '') {

            try {
                $this->ormService->db->useConnection($this->db_name);
            } catch (InvalidDatabaseException) {
                throw new InvalidDatabaseNameException('Unable to create ORM model (' . get_called_class() . '): Invalid database name');
            }

        } else {
            $this->db_name = $this->ormService->db->getCurrentConnectionName();
        }

        parent::__construct($this->ormService->events);

        $this->ormService->events->doEvent('orm.model', $this);

    }

    /**
     * Database name.
     *
     * See: https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/README.md#getconnection
     *
     * @var string (Blank for current)
     */
    protected string $db_name = '';

    /**
     * Table name.
     *
     * @var string
     */
    protected string $table_name = '';

    /**
     * Get database name.
     *
     * @return string
     */
    public function getDbName(): string
    {
        return $this->db_name;
    }

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

}