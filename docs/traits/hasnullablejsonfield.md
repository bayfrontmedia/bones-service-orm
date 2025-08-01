# [ORM service](../README.md) > [Traits](README.md) > HasNullableJsonField

The `Bayfront\BonesService\Orm\Traits\HasNullableJsonField` trait can be used by any type of [model](../models/README.md)
which has a JSON field whose keys are removed by passing a `NULL` value.

Keys can only contain alphanumeric characters, underscores and dashes.

Methods include:

- [defineNullableJsonField](#definenullablejsonfield)
- [updateNullableJsonField](#updatenullablejsonfield)

## defineNullableJsonField

**Description:**

Define nullable JSON field array, removing keys with null values.

**Parameters:**

- `$array` (array)

**Returns:**

- (array)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\InvalidFieldException`

## updateNullableJsonField

**Description:**

Update nullable JSON field array, merging updated array with existing and removing keys with null values.

**Parameters:**

- `$ormService` (`OrmService`)
- `$table_name` (string)
- `$primary_key_field` (string)
- `$primary_key` (mixed)
- `$json_field` (string)
- `$array` (array)

**Returns:**

- (array)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\InvalidFieldException`