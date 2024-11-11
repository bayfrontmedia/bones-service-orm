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
- [allowed_fields_write](#allowed_fields_write)
- [required_fields_write](#required_fields_write)
- [unique_fields](#unique_fields)
- [getMutatorFields](#getmutatorfields)
- [getDefaultFieldValues](#getdefaultfieldvalues)
- [allowed_fields_read](#allowed_fields_read)
- [getAccessorFields](#getaccessorfields)
- [search_fields](#search_fields)
- [max_related_depth](#max_related_depth)
- [default_limit](#default_limit)
- [max_limit](#max_limit)

Methods include:

- [getPrimaryKey](#getprimarykey)
- [getCount](#getcount)
- [create](#create)
- [list](#list)
- [read](#read)
- [find](#find)
- [replicate](#replicate)
- [update](#update)
- [delete](#delete)
- [exists](#exists)

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

## allowed_fields_write

Rules for any fields which can be written to the resource.
These rules are validated before being processed by [getMutatorFields](#getmutatorfields).

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

## required_fields_write

Fields required to be written to the resource on creation.

- Visibility: `protected`
- Type: `array`

## unique_fields

Unique fields whose values are checked on create/update.
The database is queried once for each key.

Uniqueness of a single field as a string, or across multiple fields as an array.

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

## getMutatorFields

**Description:**

Fields to transform when written to the database.
Returned array as `field => callable`.

The [Castable trait](../traits/castable.md) provides a variety of methods which may be used.

**Parameters:**

- (none)

**Returns:**

- (array)

## getDefaultFieldValues

**Description:**

Default field values inserted when a resource is created, if not defined.

These fields bypass [allowed_fields_write](#allowed_fields_write) rules and [mutator fields](#getmutatorfields).

**Parameters:**

- (none)

**Returns:**

- (array)

**Example:**

```php
/**
 * Default values inserted when a resource is created, if not defined.
 * 
 * These fields bypass $allowed_fields_write rules and mutator fields.
 *
 * @return array
 */
protected function getDefaultFieldValues(): array
{
    return [
        'id' => $this->createUuid(),
    ];
}
```

## allowed_fields_read

Fields which can be read from the resource.

- Visibility: `protected`
- Type: `array`

## getAccessorFields

**Description:**

Fields to transform when accessed from the database.
Returned array as `field => callable`.

The [Castable trait](../traits/castable.md) provides a variety of methods which may be used.

NOTE: Accessors do not apply as conditions when querying the database, 
such as when filtering or searching values.

**Parameters:**

- (none)

**Returns:**

- (array)

## search_fields

Fields which are searched. These fields must be [readable](#allowed_fields_read).
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

## getPrimaryKey

**Description:**

Get primary key field name.

**Parameters:**

- (none)

**Returns:**

- (string)

## getCount

**Description:**

Get total number of resources.

**Parameters:**

- (none)

**Returns:**

- (int)

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
- `Bayfront\BonesService\Orm\Exceptions\MissingFieldException`
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

**Parameters:**

- `$primary_key_id` (mixed)

**Returns:**

- (array)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\DoesNotExistException`
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
- `Bayfront\BonesService\Orm\Exceptions\MissingFieldException`
- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`

## update

**Description:**

Update existing resource.

**Parameters:**

- `$primary_key_id` (mixed)
- `$fields = []` (array)

**Returns:**

- [OrmResource](../ormresource.md)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\AlreadyExistsException`
- `Bayfront\BonesService\Orm\Exceptions\DoesNotExistException`
- `Bayfront\BonesService\Orm\Exceptions\InvalidFieldException`
- `Bayfront\BonesService\Orm\Exceptions\InvalidTraitException`
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

## exists

**Description:**

Does resource exist?

**Parameters:**

- `$primary_key_id` (mixed)

**Returns:**

- (bool)