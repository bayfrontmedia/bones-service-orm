<?php

namespace Bayfront\BonesService\Orm\Models;

use Bayfront\ArrayHelpers\Arr;
use Bayfront\Bones\Application\Utilities\App;
use Bayfront\Bones\Application\Utilities\Helpers;
use Bayfront\BonesService\Orm\Exceptions\AlreadyExistsException;
use Bayfront\BonesService\Orm\Exceptions\DoesNotExistException;
use Bayfront\BonesService\Orm\Exceptions\InvalidFieldException;
use Bayfront\BonesService\Orm\Exceptions\InvalidRequestException;
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

abstract class ResourceModel extends OrmModel
{

    /**
     * @param OrmService $ormService
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

        // Required readable fields

        $this->allowed_fields_read = array_unique(array_merge($this->allowed_fields_read, array_merge([
            $this->primary_key,
            $this->cursor_field
        ], $this->search_fields)));

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
     * Fields which are required when creating resource.
     *
     * @var array
     */
    protected array $required_fields = [];

    /**
     * Rules for any fields which can be written to the resource.
     * If a field is required, use $required_fields instead.
     *
     * See: https://github.com/bayfrontmedia/php-validator/blob/master/docs/validator.md
     *
     * @var array
     */
    protected array $allowed_fields_write = [];

    /**
     * Unique fields whose values are checked on create/update.
     * The database is queried once for each key.
     *
     * Uniqueness of a single field as a string, or across multiple fields as an array.
     *
     * @var array
     */
    protected array $unique_fields = [];

    /**
     * Fields which can be read from the resource.
     *
     * @var array
     */
    protected array $allowed_fields_read = [
        'id'
    ];

    /**
     * Fields which are searched. These fields must be readable.
     * For best performance, all searchable fields should be indexed.
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

        foreach ($this->related_fields as $column => $namespaced_class) {

            if (isset($fields[$column])) {

                $related_model = $this->getRelatedModel($namespaced_class);

                if (!$related_model->exists($fields[$column])) {
                    throw new DoesNotExistException('Unable to ' . $action . ' resource: Related field (' . $column . ') does not exist');
                }

            }

        }

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

    /**
     * Get related model instance.
     *
     * @param string $namespaced_class
     * @return ResourceModel
     * @throws UnexpectedException
     */
    private function getRelatedModel(string $namespaced_class): ResourceModel
    {

        if (!is_subclass_of($namespaced_class, ResourceModel::class)) {
            throw new UnexpectedException('Unable to get related model: Class (' . $namespaced_class . ') does not extend ' . ResourceModel::class);
        }


        if (isset($this->resource_instances[$namespaced_class])) {

            return $this->resource_instances[$namespaced_class];

        } else {

            try {

                /** @var ResourceModel $rel_model */
                $rel_model = App::make($namespaced_class);

            } catch (Exception) {
                throw new UnexpectedException('Unable to get related model: Unable to create class (' . $namespaced_class . ')');
            }

            $this->resource_instances[$namespaced_class] = $rel_model;

            return $rel_model;

        }

    }

    /**
     * Ensure filter is only applied once per model.
     *
     * @var array
     */
    private array $soft_deleted_models = [];

    /**
     * Filter soft-deleted related fields.
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

    /*
     * $jonied_tables can only be used when a table is only joined one time.
     * If a foreign key table is joined multiple times on the same table,
     * it must be given a unique alias, as it is joined to a different column on the table.
     *
     * Therefore, $joined_tables is not used.
     */
    private array $joined_tables = []; // key = table, value = alias

    private int $join_count = 0;

    private function getTableAlias(string $table_name): string
    {
        $this->join_count++;

        preg_match_all('/(?:^|_)([a-zA-Z])/', $table_name, $matches);
        return implode('', $matches[1]) . $this->join_count;
    }

    /**
     * Add field(s) to list query.
     *
     * @param Query $query
     * @param ResourceModel $model
     * @param array $fields
     * @param string $column
     * @param string|null $alias
     * @return void
     * @throws InvalidRequestException
     * @throws UnexpectedException
     */
    private function selectListFields(Query $query, ResourceModel $model, array $fields, string $column = '', ?string $alias = null): void
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

                            if (!isset($this->joined_tables[$rel_model->getTableName()])) {

                                $rel_alias = $this->getTableAlias($rel_model->getTableName());

                                $this->list_joins[$rel_model->getTableName() . ' AS ' . $rel_alias] = [
                                    $model->getTableName() . '.' . $allowed => $rel_alias . '.' . $rel_model->primary_key
                                ];

                                // Do not reuse alias
                                //$this->joined_tables[$rel_model->getTableName()] = $rel_alias;

                            } else {
                                $rel_alias = $this->joined_tables[$rel_model->table_name];
                            }

                            $this->selectListFields($query, $rel_model, [$field_exp[1]], ltrim($column . '.' . $allowed, '.'), $rel_alias);

                        } else {

                            if ($model::class === $this::class) {
                                $query->select([$allowed]);
                            } else { // Prefix

                                $sel_table = $model->table_name;

                                if (is_string($alias)) {
                                    $sel_table = $alias;
                                }

                                $query->select($sel_table . '.' . $allowed . " AS '" . $this->getPrefixedField($model->table_name, $allowed, $column) . "'");

                            }

                        }

                    }

                } else if (isset($model->related_fields[$field_exp[0]]) && in_array($field_exp[0], $model->allowed_fields_read)) {

                    $rel_model = $this->getRelatedModel($model->related_fields[$field_exp[0]]);

                    if (!isset($this->joined_tables[$rel_model->getTableName()])) {

                        $rel_alias = $this->getTableAlias($rel_model->getTableName());

                        $this->list_joins[$rel_model->getTableName() . ' AS ' . $rel_alias] = [
                            $model->getTableName() . '.' . $field_exp[0] => $rel_alias . '.' . $rel_model->primary_key
                        ];

                        // Do not reuse alias
                        //$this->joined_tables[$rel_model->getTableName()] = $rel_alias;

                    } else {
                        $rel_alias = $this->joined_tables[$rel_model->table_name];
                    }

                    $this->selectListFields($query, $rel_model, [$field_exp[1]], ltrim($column . '.' . $field_exp[0], '.'), $rel_alias);

                } else {
                    throw new InvalidRequestException('Unable to list resource: Invalid related field (' . $field . ')');
                }

            } else { // This model

                if ($field == '*') {

                    if ($model::class === $this::class) { // No prefix needed
                        $query->select($model->allowed_fields_read);
                    } else { // Prefix

                        foreach ($model->allowed_fields_read as $allowed) {

                            $sel_table = $model->table_name;

                            if (is_string($alias)) {
                                $sel_table = $alias;
                            }

                            $query->select($sel_table . '.' . $allowed . " AS '" . $this->getPrefixedField($model->table_name, $allowed, $column) . "'");

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

                        $sel_table = $model->table_name;

                        if (is_string($alias)) {
                            $sel_table = $alias;
                        }

                        $query->select($sel_table . '.' . $field . " AS '" . $this->getPrefixedField($model->table_name, $field, $column) . "'");

                        $this->filterSoftDeletedRelatedFields($query, $model);

                    }

                } else if (in_array($field, $model->allowed_fields_read)) {

                    if ($model::class === $this::class) { // No prefix needed
                        $query->select($field);
                    } else {

                        $sel_table = $model->table_name;

                        if (is_string($alias)) {
                            $sel_table = $alias;
                        }

                        $query->select($sel_table . '.' . $field . " AS '" . $this->getPrefixedField($model->table_name, $field, $column) . "'");

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

            try {
                $query->startGroup($query::CONDITION_AND);
            } catch (QueryException) {
                throw new UnexpectedException('Unable to list resource: Error performing search');
            }

            foreach ($search_fields as $field) {

                try {

                    /*
                     * Allow case-insensitive search of JSON fields
                     */

                    $query->orWhere('LOWER(' . $field . ')', $query::OPERATOR_HAS_INSENSITIVE, strtolower($search));

                } catch (QueryException) {
                    throw new UnexpectedException('Unable to list resource: Invalid search operator');
                }

            }

            $query->endGroup();

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
     * @param ResourceModel $model
     * @param QueryParserInterface $parser
     * @param array $resource
     * @return array
     */
    private function removeListFields(ResourceModel $model, QueryParserInterface $parser, array $resource): array
    {

        if ($model::class !== $this::class) { // Ignore related fields
            return $resource;
        }

        $fields = $parser->getFields();

        /*
         * If $fields is empty (no fields were requested),
         * all readable fields are returned (see getListFields).
         * Do not remove the cursor field.
         */

        if (empty($fields)) {
            return $resource;
        }

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
     *
     * @param ResourceModel $model
     * @param QueryParserInterface $parser
     * @param array $resource
     * @return array
     * @throws UnexpectedException
     */
    private function postListFunctions(ResourceModel $model, QueryParserInterface $parser, array $resource): array
    {

        $resource = $this->removeListFields($model, $parser, $resource);

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

                $value = $rel_model->onRead($value);

                // Keep checking levels until no more related resources exist

                $resource[$col] = $this->postListFunctions($rel_model, $parser, $value);

            }

        }

        return $this->onRead($resource);

    }

    /**
     * Ensure cursor field is always added.
     * If no fields are specified, all readable fields will be returned.
     *
     * @param QueryParserInterface $parser
     * @return array
     */
    private function getListFields(QueryParserInterface $parser): array
    {

        $fields = $parser->getFields();

        if (empty($fields)) {
            return $this->allowed_fields_read;
        }

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
     * Function which has begun.
     *
     * @var string
     */
    private string $has_begun = '';

    /**
     * Called before any actionable method is executed.
     *
     * Protected visibility for use in traits.
     *
     * @param string $function (Name of function)
     * @return void
     */
    protected function doBegin(string $function): void
    {
        if ($this->has_begun == '') {
            $this->has_begun = $function;
            $this->onBegin($function);
        }
    }

    /**
     * Called after any actionable method is executed.
     *
     * Protected visibility for use in traits.
     *
     * @param string $function (Name of function)
     * @return void
     */
    protected function doComplete(string $function): void
    {
        if ($this->has_begun == $function) {

            if (in_array(SoftDeletes::class, Helpers::classUses($this))) {
                /** @var SoftDeletes $this */
                $this->resetTrashedFilters();
            }

            $this->has_begun = '';
            $this->onComplete($function);
        }
    }

    /*
     * |--------------------------------------------------------------------------
     * | Actions
     * |--------------------------------------------------------------------------
     */

    /**
     * Filter fields before creating resource.
     *
     * @param array $fields
     * @return array
     */
    protected function onCreating(array $fields): array
    {
        return $fields;
    }

    /**
     * Actions to perform after a resource is created.
     *
     * @param OrmResource $resource
     * @return void
     */
    protected function onCreated(OrmResource $resource): void
    {

    }

    /**
     * Filter query before reading resource(s).
     *
     * @param Query $query
     * @return Query
     */
    protected function onReading(Query $query): Query
    {
        return $query;
    }

    /**
     * Filter fields after a resource is read.
     *
     * @param array $fields
     * @return array
     */
    protected function onRead(array $fields): array
    {
        return $fields;
    }

    /**
     * Filter fields before updating resource.
     *
     * @param OrmResource $existing
     * @param array $fields (Fields to update)
     * @return array
     * @noinspection PhpUnusedParameterInspection
     */
    protected function onUpdating(OrmResource $existing, array $fields): array
    {
        return $fields;
    }

    /**
     * Actions to perform after a resource is updated.
     *
     * @param OrmResource $resource (Newly updated resource)
     * @param OrmResource $previous (Previously existing resource)
     * @param array $fields (Updated fields)
     * @return void
     */
    protected function onUpdated(OrmResource $resource, OrmResource $previous, array $fields): void
    {

    }

    /**
     * Filter fields before writing to resource (creating and updating).
     *
     * @param array $fields
     * @return array
     */
    protected function onWriting(array $fields): array
    {
        return $fields;
    }

    /**
     * Actions to perform after a resource is written (created and updated).
     *
     * @param OrmResource $resource
     * @return void
     */
    protected function onWritten(OrmResource $resource): void
    {

    }

    /**
     * Actions to perform before a resource is deleted.
     *
     * @param OrmResource $resource
     * @return void
     */
    protected function onDeleting(OrmResource $resource): void
    {

    }

    /**
     * Actions to perform after a resource is deleted.
     *
     * @param OrmResource $resource
     * @return void
     */
    protected function onDeleted(OrmResource $resource): void
    {

    }

    /**
     * Called before any actionable ResourceModel function is executed.
     * Functions executed inside another are ignored.
     * The name of the function is passed as a parameter.
     *
     * @param string $function (Function which began)
     * @return void
     */
    protected function onBegin(string $function): void
    {

    }

    /**
     * Called after any actionable ResourceModel function is executed.
     * Functions executed inside another are ignored.
     * The name of the function is passed as a parameter.
     *
     * @param string $function (Function which completed)
     * @return void
     */
    protected function onComplete(string $function): void
    {

    }

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
     * Get fields which are required when creating resource.
     *
     * @return array
     */
    public function getRequiredFields(): array
    {
        return $this->required_fields;
    }

    /**
     * Get rules for any fields which can be written to the resource.
     *
     * @return array
     */
    public function getAllowedFieldsWrite(): array
    {
        return $this->allowed_fields_write;
    }

    /**
     * Get fields which can be read from the resource.
     *
     * @return array
     */
    public function getAllowedFieldsRead(): array
    {
        return $this->allowed_fields_read;
    }

    /**
     * Get total number of resources.
     *
     * Query is filtered through the onReading method.
     *
     * @return int
     * @throws UnexpectedException
     */
    public function getCount(): int
    {

        $this->doBegin(__FUNCTION__);

        $query = $this->newQuery();
        $query->table($this->table_name)->select($this->primary_key);

        // Trait: SoftDeletes

        if (in_array(SoftDeletes::class, Helpers::classUses($this))) {

            /** @var SoftDeletes $this */
            $deleted_at_field = $this->getDeletedAtField();

            if ($this->only_trashed === true) {

                try {
                    $query->where($deleted_at_field, $query::OPERATOR_NOT_NULL, true);
                } catch (QueryException) {
                    throw new UnexpectedException('Unable to get count: Error building query');
                }

            }

            if ($this->with_trashed === false) {

                try {
                    $query->where($deleted_at_field, $query::OPERATOR_NULL, true);
                } catch (QueryException) {
                    throw new UnexpectedException('Unable to get count: Error building query');
                }

            }

        }

        $query = $this->onReading($query);

        /*
         * Query
         */

        $start_time = microtime(true);
        $count = $query->aggregate($query::AGGREGATE_COUNT);
        $this->ormService->db->setQueryTime($this->ormService->db->getCurrentConnectionName(), microtime(true) - $start_time);

        $this->doComplete(__FUNCTION__);

        return (int)$count;

    }

    /**
     * Does resource exist?
     *
     * Query is filtered through the onReading method.
     *
     * @param mixed $primary_key_id
     * @return bool
     * @throws UnexpectedException
     */
    public function exists(mixed $primary_key_id): bool
    {

        $this->doBegin(__FUNCTION__);

        $query = $this->newQuery();

        try {

            $query->table($this->table_name)
                ->select($this->primary_key)
                ->where($this->getPrimaryKey(), $query::OPERATOR_EQUALS, $primary_key_id);

        } catch (QueryException) {
            throw new UnexpectedException('Unable to query exists: Error building query');
        }

        // Trait: SoftDeletes

        if (in_array(SoftDeletes::class, Helpers::classUses($this))) {

            /** @var SoftDeletes $this */
            $deleted_at_field = $this->getDeletedAtField();

            if ($this->only_trashed === true) {

                try {
                    $query->where($deleted_at_field, $query::OPERATOR_NOT_NULL, true);
                } catch (QueryException) {
                    throw new UnexpectedException('Unable to query exists: Error building query');
                }

            }

            if ($this->with_trashed === false) {

                try {
                    $query->where($deleted_at_field, $query::OPERATOR_NULL, true);
                } catch (QueryException) {
                    throw new UnexpectedException('Unable to query exists: Error building query');
                }

            }

        }

        $query = $this->onReading($query);

        /*
         * Query
         */

        $start_time = microtime(true);
        $count = $query->aggregate($query::AGGREGATE_COUNT);
        $this->ormService->db->setQueryTime($this->ormService->db->getCurrentConnectionName(), microtime(true) - $start_time);

        $this->doComplete(__FUNCTION__);

        return $count > 0;

    }

    /**
     * Create new resource.
     *
     * @param array $fields
     * @return OrmResource
     * @throws AlreadyExistsException
     * @throws DoesNotExistException
     * @throws InvalidFieldException
     * @throws UnexpectedException
     */
    public function create(array $fields): OrmResource
    {

        $this->doBegin(__FUNCTION__);

        // Required fields

        foreach ($this->required_fields as $field) {
            if (!isset($fields[$field])) {
                throw new InvalidFieldException('Unable to create resource: Required field (' . $field . ') does not exist');
            }
        }

        // Allowed fields/validation

        $this->validateAllowedFieldsWrite($fields, __FUNCTION__);

        // Related fields

        $this->checkRelatedFieldsExist($fields, __FUNCTION__);

        // Unique fields

        if ($this->is_upsert === false) {

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

        }

        $fields = $this->onWriting($this->onCreating($fields));

        // Create

        if ($this->is_upsert === false) {
            $create = $this->ormService->db->insert($this->table_name, $fields, false);
        } else {
            $create = $this->ormService->db->insert($this->table_name, $fields); // Overwrite
        }

        if (!$create) {
            throw new UnexpectedException('Unable to create resource');
        }

        $pk = $fields[$this->primary_key] ?? $this->ormService->db->lastInsertId();

        $resource = $this->find($pk);

        $this->onCreated($resource);
        $this->onWritten($resource);

        $this->ormService->events->doEvent('orm.resource.created', $resource);

        $this->doComplete(__FUNCTION__);

        return $resource;

    }

    /**
     * Tables which have been sorted.
     *
     * @var array
     */
    private array $sorted_join_tables = []; // key = table, value = table or alias

    /**
     * Sort joined tables to ensure aliases are being used, if existing.
     *
     * @param array $array
     * @return array
     */
    private function sortListJoins(array $array): array
    {

        /*
         * $array: key = table, value = array of join cols where key = col1 and value = col2
         */

        $return = [];

        foreach ($array as $table => $cols) { // $table = "table" or "table AS alias"

            foreach ($cols as $col1 => $col2) { // $col1, $col2 used by $query->leftJoin

                $table_exp = explode(' AS ', $table, 2);

                $col1_exp = explode('.', $col1, 2); // $col1_exp[0] = table, $col1_exp[1] = column

                if (isset($this->sorted_join_tables[$col1_exp[0]])) { // If a known table/alias

                    // Overwrite original $col1 using known table/alias

                    $col1 = $this->sorted_join_tables[$col1_exp[0]];

                    if (isset($col1_exp[1])) { // Append column, if existing
                        $col1 = $col1 . '.' . $col1_exp[1];
                    }

                } else { // Define table/alias

                    if (isset($table_exp[1])) { // If an alias
                        $this->sorted_join_tables[$table_exp[0]] = $table_exp[1];
                    } else { // If a table
                        $this->sorted_join_tables[$table] = $table;
                    }

                }

                $return[$table] = [
                    $col1 => $col2,
                ];

            }

        }

        $this->sorted_join_tables = []; // Reset

        return $return;

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

        $this->doBegin(__FUNCTION__);

        $query = $this->newQuery();
        $query->table($this->table_name);

        /*
         * Fields
         */

        $this->selectListFields($query, $this, $this->getListFields($parser));

        $joins = $this->sortListJoins($this->list_joins);

        foreach ($joins as $table => $cols) {
            foreach ($cols as $col1 => $col2) {
                $query->leftJoin($table, $col1, $col2);
            }
        }

        $this->list_joins = []; // Reset
        $this->joined_tables = []; // Reset
        $this->join_count = 0; // Reset

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

        $query = $this->onReading($query);

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

        $this->doComplete(__FUNCTION__);

        return new OrmCollection($this, $query, $parser, $get, $this->cursor_field, $limit);

    }

    /**
     * Get entire resource.
     *
     * @param mixed $primary_key_id
     * @param array $fields (Fields to read, or empty for all readable)
     * @return array
     * @throws DoesNotExistException
     * @throws InvalidRequestException
     * @throws UnexpectedException
     */
    public function read(mixed $primary_key_id, array $fields = []): array
    {

        $this->doBegin(__FUNCTION__);

        if (empty($fields)) {

            $select = $this->allowed_fields_read;

        } else {

            foreach ($fields as $field) {
                if (!in_array($field, $this->allowed_fields_read)) {
                    throw new InvalidRequestException('Unable to read resource: Invalid field (' . $field . ')');
                }
            }

            $select = $fields;

        }

        try {

            $query = $this->newQuery();
            $query->table($this->table_name)
                ->select($select)
                ->where($this->primary_key, Query::OPERATOR_EQUALS, $primary_key_id);

        } catch (QueryException) {
            throw new UnexpectedException('Unable to read resource: Error building query');
        }

        // Trait: SoftDeletes

        if (in_array(SoftDeletes::class, Helpers::classUses($this))) {

            /** @var SoftDeletes $this */
            $deleted_at_field = $this->getDeletedAtField();

            if ($this->only_trashed === true) {

                try {
                    $query->where($deleted_at_field, $query::OPERATOR_NOT_NULL, true);
                } catch (QueryException) {
                    throw new UnexpectedException('Unable to read resource: Error building query');
                }

            } else if ($this->with_trashed === false) {

                try {
                    $query->where($deleted_at_field, $query::OPERATOR_NULL, true);
                } catch (QueryException) {
                    throw new UnexpectedException('Unable to read resource: Error building query');
                }

            }

        }

        $query = $this->onReading($query);

        /*
         * Query
         */

        $start_time = microtime(true);
        $resource = $query->row();
        $this->ormService->db->setQueryTime($this->ormService->db->getCurrentConnectionName(), microtime(true) - $start_time);

        if (!$resource) {
            throw new DoesNotExistException('Resource does not exist');
        }

        $resource = $this->onRead($resource);

        $this->doComplete(__FUNCTION__);

        return $resource;

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
        $this->doBegin(__FUNCTION__);
        $resource = new OrmResource($this, $primary_key_id);
        $this->doComplete(__FUNCTION__);
        return $resource;
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
     * @throws UnexpectedException
     */
    public function replicate(mixed $primary_key_id, array $fields = []): OrmResource
    {

        $this->doBegin(__FUNCTION__);

        $this->validateAllowedFieldsWrite($fields, __FUNCTION__);

        // Only keep allowed fields to write

        try {
            $existing = Arr::only($this->read($primary_key_id), array_keys($this->allowed_fields_write));
        } catch (InvalidRequestException) {
            throw new UnexpectedException('Unable to replicate resource: Error reading existing resource');
        }

        // Merge with new field values

        $resource = $this->create(array_merge($existing, $fields));

        $this->doComplete(__FUNCTION__);

        return $resource;

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
     * @throws UnexpectedException
     */
    public function update(mixed $primary_key_id, array $fields): OrmResource
    {

        $this->doBegin(__FUNCTION__);

        // Check exists

        $previous = $this->find($primary_key_id);

        // Allowed fields/validation

        $this->validateAllowedFieldsWrite($fields, __FUNCTION__);

        // Related fields

        $this->checkRelatedFieldsExist($fields, __FUNCTION__);

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

                        $uniques = Arr::only(array_merge($previous->read(), $fields), $field);

                        foreach ($uniques as $k => $v) {
                            $query->where($k, Query::OPERATOR_EQUALS, $v);
                        }

                    } catch (QueryException) {
                        throw new UnexpectedException('Unable to update resource: Invalid operator when checking unique fields');
                    }

                    /*
                     * Query
                     */

                    $start_time = microtime(true);
                    $get = $query->get();
                    $this->ormService->db->setQueryTime($this->ormService->db->getCurrentConnectionName(), microtime(true) - $start_time);

                    if (count($get) > 0) {
                        throw new AlreadyExistsException('Unable to update resource: Unique fields (' . implode(', ', $field) . ') already exists');
                    }

                }

            }

        }

        $fields = $this->onWriting($this->onUpdating($previous, $fields));

        // Update

        $this->ormService->db->update($this->table_name, $fields, [
            $this->primary_key => $primary_key_id
        ]);

        $resource = $this->find($primary_key_id);

        $this->onUpdated($resource, $previous, $fields);
        $this->onWritten($resource);

        $this->ormService->events->doEvent('orm.resource.updated', $resource, $previous, $fields);

        $this->doComplete(__FUNCTION__);

        return $resource;

    }

    private bool $is_upsert = false;

    /**
     * Upsert resource.
     *
     * This will create a new resource, or update a resource if one with matching unique field values already exist.
     * A DoesNotExistException will be thrown if the resource is soft-deleted and the methods
     * withTrashed or onlyTrashed are not used.
     *
     * @param array $fields
     * @return OrmResource
     * @throws DoesNotExistException
     * @throws InvalidFieldException
     * @throws UnexpectedException
     */
    public function upsert(array $fields): OrmResource
    {

        $this->doBegin(__FUNCTION__);

        $this->is_upsert = true;

        try {
            $resource = $this->create($fields);
        } catch (AlreadyExistsException) {
            throw new UnexpectedException('Unexpected error upserting resource');
        }

        $this->is_upsert = false;

        $this->doComplete(__FUNCTION__);

        return $resource;

    }

    /**
     * Delete single resource.
     *
     * To permanently delete a soft-deleted resource, use the hardDelete() method instead.
     *
     * @param mixed $primary_key_id
     * @return bool
     * @throws UnexpectedException
     */
    public function delete(mixed $primary_key_id): bool
    {

        $this->doBegin(__FUNCTION__);

        try {
            $resource = $this->find($primary_key_id);
        } catch (DoesNotExistException) {
            $this->doComplete(__FUNCTION__);
            return false;
        }

        $this->onDeleting($resource);

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
                $this->onTrashed($resource);
                $this->ormService->events->doEvent('orm.resource.trashed', $resource);
                $this->doComplete(__FUNCTION__);
                return true;
            }

        } else {

            $deleted = $this->ormService->db->delete($this->table_name, [
                $this->primary_key => $primary_key_id
            ]);

            if ($deleted) { // Ensure deleted from db
                $this->onDeleted($resource);
                $this->ormService->events->doEvent('orm.resource.deleted', $resource);
                $this->doComplete(__FUNCTION__);
                return true;
            }

        }

        $this->doComplete(__FUNCTION__);
        return false;

    }

}