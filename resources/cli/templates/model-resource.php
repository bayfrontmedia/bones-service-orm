<?php

namespace _namespace_\Models;

use Bayfront\Bones\Exceptions\InvalidConfigurationException;
use Bayfront\BonesService\Orm\Models\ResourceModel;
use Bayfront\BonesService\Orm\Exceptions\InvalidDatabaseNameException;
use Bayfront\BonesService\Orm\OrmService;

/**
 * _model_name_ model.
 *
 * Created with Bones v_bones_version_ using the Bones ORM service.
 */
class _model_name_ extends ResourceModel
{

    /**
     * The container will resolve any dependencies.
     * OrmService and Db are required by the abstract model.
     *
     * @param OrmService $ormService
     * @throws InvalidConfigurationException
     * @throws InvalidDatabaseNameException
     */

    public function __construct(OrmService $ormService)
    {
        parent::__construct($ormService);
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
     * Primary key field.
     * This field must be readable.
     *
     * @var string
     */
    protected string $primary_key = 'id';

    /**
     * Unique, sequential field to use for cursor-based pagination.
     * This field must be readable.
     *
     * @var string
     */
    protected string $cursor_field = 'id';

    /**
     * Related field definitions as:
     * column => ResourceModel::class
     *
     * This associates the column in this model's table with the primary key of the related ResourceModel.
     *
     * @var array
     */
    protected array $related_fields = [];

    /**
     * Rules for any fields which can be written to the resource.
     *
     * See: https://github.com/bayfrontmedia/php-validator/blob/master/docs/validator.md
     *
     * @var array
     */
    protected array $allowed_fields_write = [];

    /**
     * Fields required to be written to the resource on creation.
     *
     * @var array
     */
    protected array $required_fields_write = [];

    /**
     * Unique fields whose values are checked on create/update.
     *
     * Uniqueness of a single field as a string, or across multiple fields as an array.
     *
     * @var array
     */
    protected array $unique_fields = [];

    /**
     * Fields to transform when written to the database.
     * Returned array as: field => callable
     *
     * The Castable trait provides a variety of methods which may be used.
     *
     * @return array
     */
    protected function getMutatorFields(): array
    {
        return [];
    }

    /**
     * Default field values inserted when a resource is created, if not defined.
     *
     * These fields bypass $allowed_fields_write rules and mutator fields.
     *
     * @return array
     */
    protected function getDefaultFieldValues(): array
    {
        return [];
    }

    /**
     * Fields which can be read from the resource.
     *
     * @var array
     */
    protected array $allowed_fields_read = [
        'id'
    ];

    /**
     * Fields to transform when accessed from the database.
     * Returned array as: field => callable
     *
     * The Castable trait provides a variety of methods which may be used.
     *
     * @return array
     */
    protected function getAccessorFields(): array
    {
        return [];
    }

    /**
     * Fields which are searched.
     * These fields must be readable.
     *
     * When empty, all readable fields will be used.
     *
     * @var array
     */
    protected array $search_fields = [];

    /**
     * Maximum related field depth allowed to query.
     * If set, this value overrides the ORM service config value.
     *
     * @var int
     */
    protected int $max_related_depth = 3;

    /**
     * Default query limit when none is specified.
     * If set, this value overrides the ORM service config value.
     *
     * @var int
     */
    protected int $default_limit = 100;

    /**
     * Maximum limit allowed to query, or -1 for unlimited.
     * If set, this value overrides the ORM service config value.
     *
     * @var int
     */
    protected int $max_limit = -1;

    /*
     * |--------------------------------------------------------------------------
     * | Model-specific
     * |--------------------------------------------------------------------------
     */

}