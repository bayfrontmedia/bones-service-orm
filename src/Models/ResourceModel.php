<?php

namespace Bayfront\BonesService\Orm\Models;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Application\Utilities\Helpers;
use Bayfront\BonesService\Orm\Exceptions\AlreadyExistsException;
use Bayfront\BonesService\Orm\Exceptions\DoesNotExistException;
use Bayfront\BonesService\Orm\Exceptions\InvalidConfigurationException;
use Bayfront\BonesService\Orm\Exceptions\InvalidDatabaseNameException;
use Bayfront\BonesService\Orm\Exceptions\InvalidFieldException;
use Bayfront\BonesService\Orm\Exceptions\InvalidRequestException;
use Bayfront\BonesService\Orm\Exceptions\InvalidTraitException;
use Bayfront\BonesService\Orm\Exceptions\MissingFieldException;
use Bayfront\BonesService\Orm\Exceptions\UnexpectedException;
use Bayfront\BonesService\Orm\Interfaces\QueryParserInterface;
use Bayfront\BonesService\Orm\OrmCollection;
use Bayfront\BonesService\Orm\OrmResource;
use Bayfront\BonesService\Orm\OrmService;
use Bayfront\BonesService\Orm\Traits\SoftDeletes;
use Bayfront\SimplePdo\Exceptions\QueryException;
use Bayfront\SimplePdo\Query;
use Bayfront\Validator\Validator;
use Exception;

/**
 * Unique resources.
 */
abstract class ResourceModel extends OrmModel
{

    /**
     * @param OrmService $ormService
     * @throws InvalidConfigurationException
     * @throws InvalidDatabaseNameException
     */
    public function __construct(OrmService $ormService)
    {
        if (!isset($this->default_limit)) {
            $this->default_limit = (int)$ormService->getConfig('resource.default_limit', 100);
        }

        if (!isset($this->max_limit)) {
            $this->max_limit = (int)$ormService->getConfig('resource.max_limit', -1);
        }

        if (!isset($this->max_related_depth)) {
            $this->max_related_depth = (int)$ormService->getConfig('resource.max_related_depth', 3);
        }

        if (!in_array($this->primary_key, $this->allowed_fields_read)) {
            throw new InvalidConfigurationException('Unable to initialize resource (' . self::class . '): Primary key must be readable');
        }

        if (!in_array($this->cursor_field, $this->allowed_fields_read)) {
            throw new InvalidConfigurationException('Unable to initialize resource (' . self::class . '): Cursor field must be readable');
        }

        if (!empty(Arr::except($this->search_fields, $this->allowed_fields_read))) {
            throw new InvalidConfigurationException('Unable to initialize resource (' . self::class . '): Search fields must be readable');
        }

        parent::__construct($ormService);
    }

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
     *
     * @var int
     */
    protected int $max_related_depth;

    /**
     * Default query limit when none is specified.
     *
     * @var int
     */
    protected int $default_limit;

    /**
     * Maximum limit allowed to query, or -1 for unlimited.
     *
     * @var int
     */
    protected int $max_limit;

    /**
     * Validate allowed fields before create/update.
     *
     * @param array $fields
     * @param string $action
     * @return void
     * @throws InvalidFieldException
     */
    private function validateAllowedFieldsWrite(array $fields, string $action): void
    {

        if (!empty(Arr::except($fields, array_keys($this->allowed_fields_write)))) {
            throw new InvalidFieldException('Unable to ' . $action . ' resource: Invalid field name(s)');
        }

        $validator = new Validator();

        $validator->validate($fields, $this->allowed_fields_write, false, true);

        if (!$validator->isValid()) {

            foreach ($validator->getMessages() as $field => $message) {
                $message = array_shift($message);
                throw new InvalidFieldException('Unable to ' . $action . ' resource: ' . $message . ' (' . $field . ')');
            }

        }

    }

    /**
     * Check related fields exist before create/update.
     *
     * NOTE:
     * Soft-deleted related fields are never included.
     *
     * @param array $fields
     * @param string $action
     * @return void
     * @throws DoesNotExistException
     * @throws UnexpectedException
     */
    private function checkRelatedFieldsExist(array $fields, string $action): void
    {

        foreach ($this->related_fields as $column => $resource_model) {

            if (isset($fields[$column])) {

                if (!is_subclass_of($resource_model, ResourceModel::class)) {
                    throw new DoesNotExistException('Unable to ' . $action . ' resource: Related field (' . $column . ') does not extend ' . ResourceModel::class);
                }

                try {
                    /** @var ResourceModel $related_model */
                    $related_model = App::make($resource_model);
                } catch (Exception) {
                    throw new UnexpectedException('Unable to ' . $action . ' resource: Unable to create ' . $resource_model . ' for related field (' . $column . ')');
                }

                if (!$related_model->exists($fields[$column])) {
                    throw new DoesNotExistException('Unable to ' . $action . ' resource: Related field (' . $column . ') does not exist');
                }

            }

        }

    }

    /**
     * Transform fields when writing to the database.
     *
     * @param ResourceModel $resourceModel
     * @param array $resource
     * @return array
     * @throws UnexpectedException
     */
    private function processMutators(ResourceModel $resourceModel, array $resource): array
    {

        foreach ($resourceModel->getMutatorFields() as $field => $callable) {

            if (!isset($resource[$field])) {
                continue;
            }

            if (!is_callable($callable)) {
                throw new UnexpectedException('Unable to create resource: Mutator not callable for field (' . $field . ')');
            }

            $resource[$field] = call_user_func($callable, $resource[$field]);

        }

        return $resource;

    }

    /**
     * Transform fields when accessing from the database.
     *
     * @param ResourceModel $resourceModel
     * @param array $resource
     * @return array
     * @throws UnexpectedException
     */
    private function processAccessors(ResourceModel $resourceModel, array $resource): array
    {

        foreach ($resourceModel->getAccessorFields() as $field => $callable) {

            if (!isset($resource[$field])) {
                continue;
            }

            if (!is_callable($callable)) {
                throw new UnexpectedException('Unable to read resource: Accessor not callable for field (' . $field . ')');
            }

            $resource[$field] = call_user_func($callable, $resource[$field]);

        }

        return $resource;

    }

    /**
     * Decode and validate cursor value.
     *
     * @param string $cursor
     * @param string $pagination_method
     * @return string
     * @throws InvalidRequestException
     */
    private function decodeCursor(string $cursor, string $pagination_method): string
    {

        $cursor = base64_decode($cursor);

        if ($cursor === false) {
            throw new InvalidRequestException('Unable to list resource: Invalid ' . $pagination_method . ' cursor value');
        }

        return $cursor;

    }

    /*
     * |--------------------------------------------------------------------------
     * | Start list-related methods
     * |--------------------------------------------------------------------------
     */

    /**
     * Related resource instances.
     * Key = namespaced class, value = instance
     *
     * @var array
     */
    private array $resource_instances = [];

    /**
     * Joins to add to list() query.
     * Prevents the same table from being joined more than once.
     *
     * @var array
     */
    private array $list_joins = [];

    /**
     * Get prefixed field name for SELECT AS from the selectListFields method.
     *
     * @param string $table_name
     * @param string $field
     * @param string $column
     * @return string
     */
    private function getPrefixedField(string $table_name, string $field, string $column): string
    {
        if ($column == '') {
            return $table_name . '.' . $field;
        } else { // Related field
            return $column . '.' . $field;
        }
    }

    private function getRelatedModel(string $namespaced_class): ResourceModel
    {

        /** @var ResourceModel $rel_model */

        if (isset($this->resource_instances[$namespaced_class])) {
            $rel_model = $this->resource_instances[$namespaced_class];
        } else {
            $rel_model = new $namespaced_class($this->ormService);
            $this->resource_instances[$namespaced_class] = $rel_model;
        }

        return $rel_model;

    }

    /**
     * Ensure filter is only applied once per model.
     *
     * @var array
     */
    private array $soft_deleted_models = [];

    /**
     * Filter soft deleted related fields.
     *
     * @param Query $query
     * @param ResourceModel $model
     * @return void
     * @throws InvalidRequestException
     */
    private function filterSoftDeletedRelatedFields(Query $query, ResourceModel $model): void
    {

        /*
         * Trait: SoftDeletes
         */

        if (!in_array($model::class, $this->soft_deleted_models) && in_array(SoftDeletes::class, Helpers::classUses($model))) {

            $this->soft_deleted_models[] = $model::class;

            /** @var SoftDeletes $model */
            $deleted_at_field = $model->getDeletedAtField();

            if (in_array(SoftDeletes::class, Helpers::classUses($this))) {

                /** @var SoftDeletes $this */
                if ($this->only_trashed === true) {

                    try {
                        $query->where($model->table_name . '.' . $deleted_at_field, $query::OPERATOR_NOT_NULL, true);
                    } catch (QueryException) {
                        throw new InvalidRequestException('Unable to list resource: Invalid filter for only trashed related soft delete field (' . $deleted_at_field . ')');
                    }

                } else if ($this->with_trashed === false) {

                    try {
                        $query->where($model->table_name . '.' . $deleted_at_field, $query::OPERATOR_NULL, true);
                    } catch (QueryException) {
                        throw new InvalidRequestException('Unable to list resource: Invalid filter for related soft delete field (' . $deleted_at_field . ')');
                    }

                }

            } else {

                try {
                    $query->where($model->table_name . '.' . $deleted_at_field, $query::OPERATOR_NULL, true);
                } catch (QueryException) {
                    throw new InvalidRequestException('Unable to list resource: Invalid filter for related soft delete field (' . $deleted_at_field . ')');
                }

            }

        }

    }

    /**
     * Add field(s) to list query.
     *
     * @param Query $query
     * @param ResourceModel $model
     * @param array $fields
     * @param string $column
     * @return void
     * @throws InvalidRequestException
     */
    private function selectListFields(Query $query, ResourceModel $model, array $fields, string $column = ''): void
    {

        foreach ($fields as $field) {

            if (str_contains($field, '.')) { // Related

                if (count(explode('.', $field)) > $this->max_related_depth) {
                    throw new InvalidRequestException('Unable to list resource: Request exceeds maximum related field depth (' . $this->max_related_depth . ')');
                }

                $field_exp = explode('.', $field, 2);

                if ($field_exp[0] == '*') { // *.x

                    foreach ($model->allowed_fields_read as $allowed) {

                        if (str_starts_with($field_exp[1], '*') && isset($model->related_fields[$allowed])) { // Related

                            $rel_model = $this->getRelatedModel($model->related_fields[$allowed]);

                            $this->list_joins[$rel_model->getTableName()] = [
                                $model->getTableName() . '.' . $allowed => $rel_model->getTableName() . '.' . $rel_model->primary_key
                            ];

                            $this->selectListFields($query, $rel_model, [$field_exp[1]], ltrim($column . '.' . $allowed, '.'));

                        } else {

                            if ($model::class === $this::class) {
                                $query->select([$allowed]);
                            } else { // Prefix
                                $query->select($model->table_name . '.' . $allowed . " AS '" . $this->getPrefixedField($model->table_name, $allowed, $column) . "'");
                            }

                        }

                    }

                } else if (isset($model->related_fields[$field_exp[0]]) && in_array($field_exp[0], $model->allowed_fields_read)) {

                    $rel_model = $this->getRelatedModel($model->related_fields[$field_exp[0]]);

                    $this->list_joins[$rel_model->getTableName()] = [
                        $model->getTableName() . '.' . $field_exp[0] => $rel_model->getTableName() . '.' . $rel_model->primary_key
                    ];

                    $this->selectListFields($query, $rel_model, [$field_exp[1]], ltrim($column . '.' . $field_exp[0], '.'));

                } else {
                    throw new InvalidRequestException('Unable to list resource: Invalid related field (' . $field . ')');
                }

            } else { // This model

                if ($field == '*') {

                    if ($model::class === $this::class) { // No prefix needed
                        $query->select($model->allowed_fields_read);
                    } else { // Prefix

                        foreach ($model->allowed_fields_read as $allowed) {
                            $query->select($model->table_name . '.' . $allowed . " AS '" . $this->getPrefixedField($model->table_name, $allowed, $column) . "'");
                        }

                        $this->filterSoftDeletedRelatedFields($query, $model);

                    }

                } else if (str_contains($field, '->')) {

                    $json_field = explode('->', $field, 2);

                    if (!in_array($json_field[0], $model->allowed_fields_read)) {

                        if ($column == '') {
                            $field = $json_field[0];
                        } else {
                            $field = $column . '.' . $json_field[0];
                        }

                        throw new InvalidRequestException('Unable to list resource: Invalid field (' . $field . ')');
                    }

                    if ($model::class === $this::class) { // No prefix needed
                        $query->select($field);
                    } else {

                        $query->select($model->table_name . '.' . $field . " AS '" . $this->getPrefixedField($model->table_name, $field, $column) . "'");

                        $this->filterSoftDeletedRelatedFields($query, $model);

                    }

                } else if (in_array($field, $model->allowed_fields_read)) {

                    if ($model::class === $this::class) { // No prefix needed
                        $query->select($field);
                    } else {

                        $query->select($model->table_name . '.' . $field . " AS '" . $this->getPrefixedField($model->table_name, $field, $column) . "'");

                        $this->filterSoftDeletedRelatedFields($query, $model);

                    }

                } else {

                    if ($column != '') {
                        $field = $column . '.' . $field;
                    }

                    throw new InvalidRequestException('Unable to list resource: Invalid field (' . $field . ')');
                }

            }

        }

    }

    private bool $condition_opened = false;

    /**
     * @param Query $query
     * @param array $filters
     * @param string $condition
     * @return void
     * @throws InvalidRequestException
     */
    private function filterListFields(Query $query, array $filters, string $condition): void
    {

        $conditions = [
            $query::CONDITION_AND,
            $query::CONDITION_OR
        ];

        foreach ($filters as $filter) {

            if (!is_array($filter)) {
                throw new InvalidRequestException('Unable to list resource: Invalid filter format');
            }

            foreach ($filter as $field => $val) {

                if (!in_array(strtoupper(ltrim($field, '_')), $conditions)) {

                    if (!in_array($field, $this->allowed_fields_read)) {
                        throw new InvalidRequestException('Unable to list resource: Invalid filter field (' . $field . ')');
                    }

                    $this->condition_opened = false;

                    if (!is_array($val)) {
                        throw new InvalidRequestException('Unable to list resource: Invalid filter value');
                    }

                    foreach ($val as $operator => $value) {

                        // $field = field, $operator = operator, $value = value

                        try {

                            if (strtoupper(ltrim($condition, '_')) == $query::CONDITION_OR) {
                                $query->orWhere($field, $operator, $value);
                            } else {
                                $query->where($field, $operator, $value);
                            }

                        } catch (QueryException) {
                            throw new InvalidRequestException('Unable to list resource: Invalid filter definition for field (' . $field . ')');
                        }

                    }

                } else {

                    // $field = condition, $val = array

                    if ($this->condition_opened === true) { // where/orWhere

                        $this->condition_opened = false;

                        if (!is_array($val)) {
                            throw new InvalidRequestException('Unable to list resource: Invalid filter condition definition for field (' . $field . ')');
                        }

                        foreach ($val as $definitions) {

                            if (!is_array($definitions)) {
                                throw new InvalidRequestException('Unable to list resource: Invalid filter condition value for field (' . $field . ')');
                            }

                            foreach ($definitions as $col => $definition) {

                                // $field = condition

                                if (!is_array($definition)) {
                                    throw new InvalidRequestException('Unable to list resource: Invalid filter definition value for field (' . $field . ')');
                                }

                                foreach ($definition as $operator => $v) {

                                    try {
                                        if (strtoupper(ltrim($field, '_')) == $query::CONDITION_OR) {
                                            $query->orWhere($col, $operator, $v);
                                        } else {
                                            $query->where($col, $operator, $v);
                                        }

                                    } catch (QueryException) {
                                        throw new InvalidRequestException('Unable to list resource: Invalid filter definition for field (' . $field . ')');
                                    }

                                }

                            }

                        }

                    } else { // Start group

                        $this->condition_opened = true;

                        try {
                            $query->startGroup(strtoupper(ltrim($field, '_')));
                        } catch (QueryException) {
                            throw new InvalidRequestException('Unable to list resource: Invalid filter group condition');
                        }

                        $this->filterListFields($query, $val, $field);

                        $query->endGroup();

                        $this->condition_opened = false;

                    }

                }

            }

        }

    }

    /**
     * Add case-insensitive search to list query.
     *
     * @param Query $query
     * @param string $search
     * @return void
     * @throws UnexpectedException
     */
    private function searchListFields(Query $query, string $search): void
    {

        if ($search !== '') {

            if (!empty($this->search_fields)) {
                $search_fields = $this->search_fields;
            } else {
                $search_fields = $this->allowed_fields_read;
            }

            foreach ($search_fields as $field) {

                try {
                    $query->orWhere($field, $query::OPERATOR_HAS_INSENSITIVE, $search);
                } catch (QueryException) {
                    throw new UnexpectedException('Unable to list resource: Invalid search operator');
                }

            }

        }

    }

    /**
     * Add sort/ORDER BY to list query.
     *
     * @param Query $query
     * @param array $fields
     * @return void
     * @throws InvalidRequestException
     */
    private function sortListFields(Query $query, array $fields): void
    {

        if (empty($fields)) {

            $query->orderBy([$this->primary_key]);

        } else {

            foreach ($fields as $v) {

                if (!in_array(ltrim($v, '-+'), $this->allowed_fields_read)) {
                    throw new InvalidRequestException('Unable to list resource: Invalid sort field (' . $v . ')');
                }

            }

            $query->orderBy($fields);

        }

    }

    /**
     * Add GROUP BY to list query.
     *
     * @param Query $query
     * @param array $fields
     * @return void
     * @throws InvalidRequestException
     */
    private function groupListFields(Query $query, array $fields): void
    {

        if (!empty($fields)) {

            foreach ($fields as $v) {

                if (!in_array($v, $this->allowed_fields_read)) {
                    throw new InvalidRequestException('Unable to list resource: Invalid group field (' . $v . ')');
                }

            }

            $query->groupBy($fields);

        }

    }

    /**
     * Add LIMIT to list query.
     *
     * @param Query $query
     * @param int|null $limit
     * @return int|null (Defined limit: null = no limit)
     * @throws InvalidRequestException
     */
    private function limitList(Query $query, int|null $limit): int|null
    {

        $return = null; // No limit

        if ($limit == -1) {

            /*
             * If $max_limit = -1,
             * there is no limit to apply to the query (fetch all)
             */

            if ($this->max_limit > -1) {
                $query->limit($this->max_limit);
                $return = $this->max_limit;
            }

        } else if (is_int($limit)) {

            if ($this->max_limit == -1 || $this->max_limit >= $limit) {

                $query->limit($limit);
                $return = $limit;

            } else {
                throw new InvalidRequestException('Unable to list resource: Limit (' . $limit . ') exceeds maximum limit (' . $this->max_limit . ')');
            }

        } else { // $limit is NULL

            $query->limit($this->default_limit);
            $return = $this->default_limit;

        }

        return $return;

    }

    /**
     * Add pagination to list query.
     *
     * @param Query $query
     * @param QueryParserInterface $parser
     * @param int|null $limit
     * @return void
     * @throws InvalidRequestException
     * @throws UnexpectedException
     */
    private function paginateList(Query $query, QueryParserInterface $parser, int|null $limit): void
    {

        if ($parser->getPaginationMethod() == $parser::PAGINATION_PAGE) {

            if (is_int($limit)) { // Limit must be supplied for offset

                $query->offset($limit * ($parser->getPage() - 1));

            }

        } else if ($parser->getPaginationMethod() == $parser::PAGINATION_BEFORE) {

            $value = $this->decodeCursor($parser->getBeforeCursor(), $parser::PAGINATION_BEFORE);

            try {
                $query->where($this->cursor_field, $query::OPERATOR_LESS_THAN, $value);
            } catch (QueryException) { // Only thrown when an invalid operator is used
                throw new UnexpectedException('Unable to list resource: Error applying ' . $parser::PAGINATION_BEFORE . ' cursor');
            }

        } else if ($parser->getPaginationMethod() == $parser::PAGINATION_AFTER) {

            $value = $this->decodeCursor($parser->getAfterCursor(), $parser::PAGINATION_AFTER);

            try {
                $query->where($this->cursor_field, $query::OPERATOR_GREATER_THAN, $value);
            } catch (QueryException) { // Only thrown when an invalid operator is used
                throw new UnexpectedException('Unable to list resource: Error applying ' . $parser::PAGINATION_AFTER . ' cursor');
            }

        }

    }

    /**
     * Ensure cursor field is removed if not requested.
     *
     * @param QueryParserInterface $parser
     * @param array $resource
     * @return array
     */
    private function removeListFields(QueryParserInterface $parser, array $resource): array
    {

        $fields = $parser->getFields();

        foreach ($fields as $field) {
            if ($field == $this->cursor_field || str_starts_with($field, '*')) {
                return $resource;
            }
        }

        return Arr::except($resource, $this->cursor_field);

    }

    /**
     * Functions to perform after a resources is listed.
     *
     * - Remove cursor field if not requested
     * - Convert JSON fields to dot notation
     * - Process accessors on all returned resources
     *
     * @param ResourceModel $model
     * @param QueryParserInterface $parser
     * @param array $resource
     * @return array
     * @throws UnexpectedException
     */
    private function postListFunctions(ResourceModel $model, QueryParserInterface $parser, array $resource): array
    {

        $resource = $this->removeListFields($parser, $resource);

        foreach ($resource as $col => $value) {

            /*
             * JSON fields
             */

            if (str_contains($col, '->')) {

                // Convert to dot notation

                $dot_col = str_replace('->', '.', $col);
                unset($resource[$col]);
                Arr::set($resource, $dot_col, $value);

            }

            /*
             * Related fields
             */

            if (is_array($value)
                && isset($model->related_fields[$col])
                && isset($this->resource_instances[$model->related_fields[$col]])) {

                /** @var ResourceModel $rel_model */
                $rel_model = $this->resource_instances[$model->related_fields[$col]];

                $resource[$col] = $rel_model->processAccessors($rel_model, $resource[$col]);

                // Keep checking levels until no more related resources exist

                $resource[$col] = $this->postListFunctions($rel_model, $parser, $resource[$col]);

            }

        }

        return $this->processAccessors($model, $resource);

    }

    /**
     * Ensure cursor field is always added.
     *
     * @param QueryParserInterface $parser
     * @return array
     */
    private function getListFields(QueryParserInterface $parser): array
    {

        $fields = $parser->getFields();

        foreach ($fields as $field) {
            if ($field == $this->cursor_field || str_starts_with($field, '*')) {
                return $fields;
            }
        }

        return array_merge($fields, [$this->cursor_field]);

    }

    /*
     * |--------------------------------------------------------------------------
     * | End list-related methods
     * |--------------------------------------------------------------------------
     */

    /**
     * Get primary key field name.
     *
     * @return string
     */
    public function getPrimaryKey(): string
    {
        return $this->primary_key;
    }

    /**
     * Get total number of resources.
     *
     * @return int
     */
    public function getCount(): int
    {

        // Trait: SoftDeletes

        if (in_array(SoftDeletes::class, Helpers::classUses($this))) {

            /** @var SoftDeletes $this */
            $deleted_at_field = $this->getDeletedAtField();

            if ($this->only_trashed === true) {

                $this->resetTrashedFilters();
                return $this->ormService->db->single("SELECT COUNT(*) FROM $this->table_name WHERE $deleted_at_field IS NOT NULL");

            }

            if ($this->with_trashed === false) {

                return $this->ormService->db->single("SELECT COUNT(*) FROM $this->table_name WHERE $deleted_at_field IS NULL");

            }

            $this->resetTrashedFilters();

        }

        return $this->ormService->db->count($this->table_name);

    }

    /**
     * Create new resource.
     *
     * @param array $fields
     * @return OrmResource
     * @throws AlreadyExistsException
     * @throws DoesNotExistException
     * @throws InvalidFieldException
     * @throws MissingFieldException
     * @throws UnexpectedException
     */
    public function create(array $fields): OrmResource
    {

        // Required fields

        if (Arr::isMissing($fields, $this->required_fields_write)) {
            throw new MissingFieldException('Unable to create resource: Missing required field(s)');
        }

        // Mutators

        $fields = $this->processMutators($this, $fields);

        // Allowed fields/validation

        $this->validateAllowedFieldsWrite($fields, 'create');

        // Default fields

        $fields = array_merge($this->getDefaultFieldValues(), $fields);

        // Related fields

        $this->checkRelatedFieldsExist($fields, 'create');

        // Unique fields

        foreach ($this->unique_fields as $field) {

            if (is_string($field)) {

                if (isset($fields[$field])) {

                    if ($this->ormService->db->exists($this->table_name, [
                        $field => $fields[$field]
                    ])) {
                        throw new AlreadyExistsException('Unable to create resource: Unique field (' . $field . ') already exists');
                    }

                }

            } else if (is_array($field)) {

                if (count(Arr::only($fields, $field)) == count($field)) { // If all unique fields exist

                    if ($this->ormService->db->exists($this->table_name, Arr::only($fields, $field))) {
                        throw new AlreadyExistsException('Unable to create resource: Unique fields (' . implode(', ', $field) . ') already exists');
                    }

                }

            }

        }

        // Create

        $create = $this->ormService->db->insert($this->table_name, $fields);

        if (!$create) {
            throw new UnexpectedException('Unable to create resource');
        }

        $pk = $fields[$this->primary_key] ?? $this->ormService->db->lastInsertId();

        $resource = $this->find($pk);

        $this->ormService->events->doEvent('orm.resource.create', $resource);

        return $resource;

    }

    /**
     * List resources.
     *
     * @param QueryParserInterface $parser
     * @param bool $list_all (Override any limits to list all existing)
     * @return OrmCollection
     * @throws InvalidRequestException
     * @throws UnexpectedException
     */
    public function list(QueryParserInterface $parser, bool $list_all = false): OrmCollection
    {

        $query = $this->newQuery();
        $query->table($this->table_name);

        /*
         * Fields
         */

        $this->selectListFields($query, $this, $this->getListFields($parser));

        foreach ($this->list_joins as $table => $cols) {
            foreach ($cols as $col1 => $col2) {
                $query->leftJoin($table, $col1, $col2);
            }
        }

        $this->list_joins = []; // Reset

        /*
         * Filter
         */

        $fields = Arr::dot($parser->getFilter());

        foreach ($fields as $key => $value) {
            $fields[$key] = $this->ormService->filters->doFilter('orm.query.filter', $value);
        }

        $this->filterListFields($query, Arr::undot($fields), $query::CONDITION_AND);

        /*
         * Trait: SoftDeletes
         */

        if (in_array(SoftDeletes::class, Helpers::classUses($this))) {

            /** @var SoftDeletes $this */
            $deleted_at_field = $this->getDeletedAtField();

            if ($this->only_trashed === true) {

                $this->resetTrashedFilters();

                try {
                    $query->where($deleted_at_field, $query::OPERATOR_NOT_NULL, true);
                } catch (QueryException) {
                    throw new InvalidRequestException('Unable to list resource: Invalid filter for only trashed soft delete field (' . $deleted_at_field . ')');
                }

            } else if ($this->with_trashed === false) {

                try {
                    $query->where($deleted_at_field, $query::OPERATOR_NULL, true);
                } catch (QueryException) {
                    throw new InvalidRequestException('Unable to list resource: Invalid filter for soft delete field (' . $deleted_at_field . ')');
                }

            } else {
                $this->resetTrashedFilters();
            }

        }

        /*
         * Search (case-insensitive)
         */

        $this->searchListFields($query, $parser->getSearch());

        /*
         * Sort
         */

        $this->sortListFields($query, $parser->getSort());

        /*
         * Group
         */

        $this->groupListFields($query, $parser->getGroup());

        /*
         * Limit
         */

        if ($list_all === false) {
            $limit = $this->limitList($query, $parser->getLimit());
        } else {
            $limit = null;
        }

        /*
         * Pagination
         */

        $this->paginateList($query, $parser, $limit);

        /*
         * Query
         */

        $start_time = microtime(true);

        $get = $query->get();

        $this->ormService->db->setQueryTime($this->ormService->db->getCurrentConnectionName(), microtime(true) - $start_time);

        /*
         * Undot all values (convert related fields to array)
         * Related fields are prefixed with column names.
         */

        $get = Arr::undot(Arr::dot($get));

        /*
         * Response
         */

        foreach ($get as $k => $v) {
            $get[$k] = $this->postListFunctions($this, $parser, $v);
        }

        return new OrmCollection($this, $query, $parser, $get, $this->cursor_field, $limit);

    }

    /**
     * Get entire resource.
     *
     * @param mixed $primary_key_id
     * @return array
     * @throws DoesNotExistException
     * @throws UnexpectedException
     */
    public function read(mixed $primary_key_id): array
    {

        // Trait: SoftDeletes

        if (in_array(SoftDeletes::class, Helpers::classUses($this))) {

            /** @var SoftDeletes $this */
            $deleted_at_field = $this->getDeletedAtField();

            if ($this->only_trashed === true) {

                $this->resetTrashedFilters();

                $resource = $this->ormService->db->row("SELECT " . implode(', ', $this->allowed_fields_read) . " FROM $this->table_name WHERE $this->primary_key = :primaryKey AND $deleted_at_field IS NOT NULL", [
                    'primaryKey' => $primary_key_id
                ]);

            } else if ($this->with_trashed === false) {

                $resource = $this->ormService->db->row("SELECT " . implode(', ', $this->allowed_fields_read) . " FROM $this->table_name WHERE $this->primary_key = :primaryKey AND $deleted_at_field IS NULL", [
                    'primaryKey' => $primary_key_id
                ]);

            } else {
                $this->resetTrashedFilters();
            }

        }

        if (!isset($resource)) {

            $resource = $this->ormService->db->row("SELECT " . implode(', ', $this->allowed_fields_read) . " FROM $this->table_name WHERE $this->primary_key = :primaryKey", [
                'primaryKey' => $primary_key_id
            ]);

        }

        if (!$resource) {
            throw new DoesNotExistException('Resource does not exist');
        }

        return $this->processAccessors($this, $resource);

    }

    /**
     * Return OrmResource instance for a single resource.
     *
     * @param mixed $primary_key_id
     * @return OrmResource
     * @throws DoesNotExistException
     * @throws UnexpectedException
     */
    public function find(mixed $primary_key_id): OrmResource
    {
        return new OrmResource($this, $primary_key_id);
    }

    /**
     * Replicate existing resource.
     *
     * @param mixed $primary_key_id (Resource to replicate)
     * @param array $fields (Overwrite existing field values)
     * @return OrmResource
     * @throws AlreadyExistsException
     * @throws DoesNotExistException
     * @throws InvalidFieldException
     * @throws MissingFieldException
     * @throws UnexpectedException
     */
    public function replicate(mixed $primary_key_id, array $fields = []): OrmResource
    {

        $this->validateAllowedFieldsWrite($fields, 'replicate');

        // Only keep allowed fields to write

        $existing = Arr::only($this->read($primary_key_id), array_keys($this->allowed_fields_write));

        // Merge with new field values

        return $this->create(array_merge($existing, $fields));

    }

    /**
     * Update existing resource.
     *
     * @param mixed $primary_key_id
     * @param array $fields
     * @return OrmResource
     * @throws AlreadyExistsException
     * @throws DoesNotExistException
     * @throws InvalidFieldException
     * @throws InvalidTraitException
     * @throws UnexpectedException
     */
    public function update(mixed $primary_key_id, array $fields): OrmResource
    {

        // Trait: SoftDeletes

        /*
         * Placeholders since the first read() will resetTrashedFilters()
         */

        $only_trashed = false;
        $with_trashed = false;

        if (in_array(SoftDeletes::class, Helpers::classUses($this))) { // Save current state of trashed filters
            /** @var SoftDeletes $this */
            $only_trashed = $this->only_trashed;
            $with_trashed = $this->with_trashed;
        }

        // Check exists

        $existing = $this->read($primary_key_id); // Resets trashed filters

        // Mutators

        $fields = $this->processMutators($this, $fields);

        // Allowed fields/validation

        $this->validateAllowedFieldsWrite($fields, 'update');

        // Related fields

        $this->checkRelatedFieldsExist($fields, 'update');

        // Unique fields

        foreach ($this->unique_fields as $field) {

            if (is_string($field)) {

                if (isset($fields[$field])) {

                    // Where not this ID

                    $count = $this->ormService->db->single("SELECT COUNT(*) FROM $this->table_name WHERE $field = :field AND $this->primary_key != :primaryKey", [
                        'field' => $fields[$field],
                        'primaryKey' => $primary_key_id
                    ]);

                    if ($count > 0) {
                        throw new AlreadyExistsException('Unable to update resource: Unique field (' . $field . ') already exists');
                    }

                }

            } else if (is_array($field)) {

                if (!empty(Arr::only($fields, $field))) { // If any exist

                    $query = $this->newQuery();

                    try {

                        $query->table($this->table_name)
                            ->select($this->primary_key)
                            ->where($this->primary_key, Query::OPERATOR_DOES_NOT_EQUAL, $primary_key_id);

                        $uniques = Arr::only(array_merge($existing, $fields), $field);

                        foreach ($uniques as $k => $v) {
                            $query->where($k, Query::OPERATOR_EQUALS, $v);
                        }

                    } catch (QueryException) {
                        throw new UnexpectedException('Unable to update resource: Invalid operator when checking unique fields');
                    }

                    $start_time = microtime(true);

                    if (count($query->get()) > 0) {
                        throw new AlreadyExistsException('Unable to update resource: Unique fields (' . implode(', ', $field) . ') already exists');
                    }

                    $this->ormService->db->setQueryTime($this->ormService->db->getCurrentConnectionName(), microtime(true) - $start_time);

                }

            }

        }

        // Update

        /*
         * Do not throw UnexpectedException if returns FALSE
         * because FALSE is returned if the query was successful
         * but no fields changed.
         */

        $this->ormService->db->update($this->table_name, $fields, [
            $this->primary_key => $primary_key_id
        ]);

        /*
         * Update the SoftDeletes trait filters for second read()
         */

        if (in_array(SoftDeletes::class, Helpers::classUses($this))) {

            /** @var SoftDeletes $this */

            if ($only_trashed === true) {
                $this->onlyTrashed();
            } else if ($with_trashed === true) {
                $this->withTrashed();
            }

        }

        $resource = $this->find($primary_key_id);

        $this->ormService->events->doEvent('orm.resource.update', $resource, $existing, $fields);

        return $resource;

    }

    /**
     * Delete single resource.
     *
     * To permanently delete a soft-deleted resource, use
     * the hardDelete() method instead.
     *
     * @param mixed $primary_key_id
     * @return bool
     * @throws UnexpectedException
     */
    public function delete(mixed $primary_key_id): bool
    {

        try {
            $resource = $this->find($primary_key_id);
        } catch (DoesNotExistException) {
            return false;
        }

        // Trait: SoftDeletes

        if (in_array(SoftDeletes::class, Helpers::classUses($this))) {

            /** @var SoftDeletes $this */
            $deleted_at_field = $this->getDeletedAtField();

            $deleted_at_value = date('Y-m-d H:i:s');

            /*
             * Must bypass the field validations used in the update() method
             */

            $updated = $this->ormService->db->update($this->table_name, [
                $deleted_at_field => $deleted_at_value
            ], [
                $this->primary_key => $primary_key_id
            ]);

            if ($updated) {
                $this->ormService->events->doEvent('orm.resource.trash', $resource);
                return true;
            }

        } else {

            $deleted = $this->ormService->db->delete($this->table_name, [
                $this->primary_key => $primary_key_id
            ]);

            if ($deleted) { // Ensure deleted from db
                $this->ormService->events->doEvent('orm.resource.delete', $resource);
                return true;
            }

        }

        return false;

    }

    /**
     * Does resource exist?
     *
     * @param mixed $primary_key_id
     * @return bool
     */
    public function exists(mixed $primary_key_id): bool
    {

        // Trait: SoftDeletes

        if (in_array(SoftDeletes::class, Helpers::classUses($this))) {

            /** @var SoftDeletes $this */
            $deleted_at_field = $this->getDeletedAtField();

            if ($this->only_trashed === true) {

                $this->resetTrashedFilters();

                $count = $this->ormService->db->single("SELECT COUNT(*) FROM $this->table_name WHERE $this->primary_key = :primaryKey AND $deleted_at_field IS NOT NULL", [
                    'primaryKey' => $primary_key_id
                ]);

                return $count > 0;

            }

            if ($this->with_trashed === false) {

                $count = $this->ormService->db->single("SELECT COUNT(*) FROM $this->table_name WHERE $this->primary_key = :primaryKey AND $deleted_at_field IS NULL", [
                    'primaryKey' => $primary_key_id
                ]);

                return $count > 0;

            }

            $this->resetTrashedFilters();

        }

        return $this->ormService->db->exists($this->table_name, [
            $this->primary_key => $primary_key_id
        ]);

    }

}