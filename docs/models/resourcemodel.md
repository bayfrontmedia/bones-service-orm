# [ORM service](../README.md) > [Models](README.md) > ResourceModel

The `Bayfront\BonesService\Orm\Models\ResourceModel` requires an [OrmService](../ormservice.md) instance to be passed
to the constructor, and extends [OrmModel](ormmodel.md).

All resource models require a [primary key](#primary_key) and a unique, 
sequential field to use for [cursor-based pagination](#cursor_field).
The same field can be used for both values.

A `ResourceModel` can be created with the `php bones make:ormmodel --type=resource` [console command](README.md).

Configuration includes:

- [primary_key](#primary_key)
- [cursor_field](#cursor_field)
- [related_fields](#related_fields)
- [required_fields](#required_fields)
- [allowed_fields_write](#allowed_fields_write)
- [unique_fields](#unique_fields)
- [allowed_fields_read](#allowed_fields_read)
- [search_fields](#search_fields)
- [max_related_depth](#max_related_depth)
- [default_limit](#default_limit)
- [max_limit](#max_limit)

Methods include:

- [onCreating](#oncreating)
- [onCreated](#oncreated)
- [onReading](#onreading)
- [onRead](#onread)
- [onUpdating](#onupdating)
- [onUpdated](#onupdated)
- [onWriting](#onwriting)
- [onWritten](#onwritten)
- [onDeleting](#ondeleting)
- [onDeleted](#ondeleted)
- [onBegin](#onbegin)
- [onComplete](#oncomplete)
- [getPrimaryKey](#getprimarykey)
- [getRequiredFields](#getrequiredfields)
- [getAllowedFieldsWrite](#getallowedfieldswrite)
- [getAllowedFieldsRead](#getallowedfieldsread)
- [getCount](#getcount)
- [exists](#exists)
- [create](#create)
- [list](#list)
- [read](#read)
- [find](#find)
- [replicate](#replicate)
- [update](#update)
- [upsert](#upsert)
- [delete](#delete)

## primary_key

Primary key field. This field must be [readable](#allowed_fields_read).

- Visibility: `protected`
- Type: `string`

## cursor_field

Unique, sequential field to use for cursor-based pagination. This field must be [readable](#allowed_fields_read).

- Visibility: `protected`
- Type: `string`

## related_fields

Related field definitions as `column => ResourceModel::class`.

This associates the column in the model's table with the primary key of the related `ResourceModel`.

- Visibility: `protected`
- Type: `array`

**Example:**

```php
 protected array $related_fields = [
    'user' => Users::class,
];
```

## required_fields

Fields which are required when creating resource.

- Visibility: `protected`
- Type: `array`

## allowed_fields_write

Rules for any fields which can be written to the resource.
If a field is required, use `$required_fields` instead.

See [Validator](https://github.com/bayfrontmedia/php-validator/blob/master/docs/validator.md) library.

- Visibility: `protected`
- Type: `array`

**Example:**

```php
protected array $allowed_fields_write = [
    'name' => 'isString|lengthLessThan:255',
    'description' => 'isString|lengthLessThan:255',
];
```

## unique_fields

Unique fields whose values are checked on create/update.
The database is queried once for each key.

Uniqueness of a single field as a string, or across multiple fields as an array.

**NOTE:** `null` values are always allowed.

- Visibility: `protected`
- Type: `array`

**Example:**

```php
protected array $unique_fields = [
    'email', // "email" must be unique
    [ // "name" and "tenant" combination must be unique
        'name',
        'tenant'
    ],
];
```

## allowed_fields_read

Fields which can be read from the resource.

- Visibility: `protected`
- Type: `array`

## search_fields

Fields which are searched. These fields must be [readable](#allowed_fields_read).
For best performance, all searchable fields should be indexed.

When empty, all readable fields will be used.

- Visibility: `protected`
- Type: `array`

## max_related_depth

Maximum related field depth allowed to query.
If set, this value overrides the ORM service [config value](../setup.md#configuration).

- Visibility: `protected`
- Type: `int`

## default_limit

Default query limit when none is specified.
If set, this value overrides the ORM service [config value](../setup.md#configuration).

- Visibility: `protected`
- Type: `int`

## max_limit

Maximum limit allowed to query, or `-1` for unlimited.
If set, this value overrides the ORM service [config value](../setup.md#configuration).

- Visibility: `protected`
- Type: `int`

## onCreating

**Description:**

Filter fields before creating resource.

**Parameters:**

- `$fields` (array)

**Returns:**

- (array)

## onCreated

**Description:**

Actions to perform after a resource is created.

**Parameters:**

- `$resource` [OrmResource](../ormresource.md)

**Returns:**

- (void)

## onReading

**Description:**

Filter query before reading resource(s).

**Parameters:**

- `$query` [Query](https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md)

**Returns:**

- [Query](https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md)

## onRead

**Description:**

Filter fields after a resource is read.

**Parameters:**

- `$fields` (array)

**Returns:**

- (array)

## onUpdating

**Description:**

Filter fields before updating resource.

**Parameters:**

- `$existing` [OrmResource](../ormresource.md)
- `$fields` (array): Fields to update

**Returns:**

- (array)

## onUpdated

**Description:**

Actions to perform after a resource is updated.

**Parameters:**

- `$resource` [OrmResource](../ormresource.md): Newly updated resource
- `$previous` [OrmResource](../ormresource.md): Previously existing resource
- `$fields` (array): Updated fields

**Returns:**

- (void)

## onWriting

**Description:**

Filter fields before writing to resource (creating and updating).

**Parameters:**

- `$fields` (array)

**Returns:**

- (array)

## onWritten

**Description:**

Actions to perform after a resource is written (created and updated).

**Parameters:**

- `$resource` [OrmResource](../ormresource.md)

**Returns:**

- (void)

## onDeleting

**Description:**

Actions to perform before a resource is deleted.

**Parameters:**

- `$resource` [OrmResource](../ormresource.md)

**Returns:**

- (void)

## onDeleted

**Description:**

Actions to perform after a resource is deleted.

**Parameters:**

- `$resource` [OrmResource](../ormresource.md)

**Returns:**

- (void)

## onBegin

**Description:**

Called before any actionable `ResourceModel` method is executed.
Functions executed inside another are ignored.
The name of the function is passed as a parameter.

**Parameters:**

- `$function` (string): Function which began

**Returns:**

- (void)

## onComplete

**Description:**

Called after any actionable ResourceModel method is executed.
Functions executed inside another are ignored.
The name of the function is passed as a parameter.

**Parameters:**

- `$function` (string): Function which completed

**Returns:**

- (void)

## getPrimaryKey

**Description:**

Get primary key field name.

**Parameters:**

- (none)

**Returns:**

- (string)

## getRequiredFields

**Description:**

Get fields which are required when creating resource.

**Parameters:**

- (none)

**Returns:**

- (array)

## getAllowedFieldsWrite

**Description:**

Get rules for any fields which can be written to the resource.

**Parameters:**

- (none)

**Returns:**

- (array)

## getAllowedFieldsRead

**Description:**

Get fields which can be read from the resource.

**Parameters:**

- (none)

**Returns:**

- (array)

## getCount

**Description:**

Get total number of resources.

Query is filtered through the [onReading](#onreading) method.

**Parameters:**

- (none)

**Returns:**

- (int)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`

## exists

**Description:**

Does resource exist?

Query is filtered through the [onReading](#onreading) method.

**Parameters:**

- `$primary_key_id` (mixed)

**Returns:**

- (bool)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`

## create

**Description:**

Create new resource.

**Parameters:**

- `$fields` (array)

**Returns:**

- [OrmResource](../ormresource.md)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\AlreadyExistsException`
- `Bayfront\BonesService\Orm\Exceptions\DoesNotExistException`
- `Bayfront\BonesService\Orm\Exceptions\InvalidFieldException`
- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`

## list

**Description:**

List resources.

**Parameters:**

- [QueryParserInterface](../utilities/queryparserinterface.md)
- `$list_all = false` (bool): Override any limits to list all existing

**Returns:**

- [OrmCollection](../ormcollection.md)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\InvalidRequestException`
- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`

## read

**Description:**

Get entire resource.

A [FieldParserInterface](../utilities/fieldparserinterface.md) can be used to identify requested fields.

**Parameters:**

- `$primary_key_id` (mixed)
- `$fields = []` (array): Fields to read, or empty for all readable

**Returns:**

- (array)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\DoesNotExistException`
- `Bayfront\BonesService\Orm\Exceptions\InvalidRequestException`
- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`

## find

**Description:**

Return `OrmResource` instance for a single resource.

**Parameters:**

- `$primary_key_id` (mixed)

**Returns:**

- [OrmResource](../ormresource.md)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\DoesNotExistException`
- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`

## replicate

**Description:**

Replicate existing resource.

**Parameters:**

- `$primary_key_id` (mixed): Resource to replicate
- `$fields = []` (array): Overwrite existing field values

**Returns:**

- [OrmResource](../ormresource.md)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\AlreadyExistsException`
- `Bayfront\BonesService\Orm\Exceptions\DoesNotExistException`
- `Bayfront\BonesService\Orm\Exceptions\InvalidFieldException`
- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`

## update

**Description:**

Update existing resource.

**Parameters:**

- `$primary_key_id` (mixed)
- `$fields` (array)

**Returns:**

- [OrmResource](../ormresource.md)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\AlreadyExistsException`
- `Bayfront\BonesService\Orm\Exceptions\DoesNotExistException`
- `Bayfront\BonesService\Orm\Exceptions\InvalidFieldException`
- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`

## upsert

**Description:**

Upsert resource.

This will create a new resource, or update a resource if one with matching unique field values already exist.
A `DoesNotExistException` will be thrown if the resource is soft-deleted and the methods 
[withTrashed](../traits/softdeletes.md#withtrashed) or [onlyTrashed](../traits/softdeletes.md#onlytrashed) are not used.

**Parameters:**

- `$fields` (array)

**Returns:**

- [OrmResource](../ormresource.md)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\DoesNotExistException`
- `Bayfront\BonesService\Orm\Exceptions\InvalidFieldException`
- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`

## delete

**Description:**

Delete single resource.

To permanently delete a soft-deleted resource, use the [hardDelete](../traits/softdeletes.md#harddelete) method instead.

**Parameters:**

- `$primary_key_id` (mixed)

**Returns:**

- (bool)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`