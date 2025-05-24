# [ORM service](../README.md) > [Traits](README.md) > HasNullableJsonField

The `Bayfront\BonesService\Orm\Traits\HasNullableJsonField` trait can be used by any type of [model](../models/README.md)
which has a JSON field whose keys are removed by passing a `NULL` value.

Configuration includes:

- [getNullableJsonField](#getnullablejsonfield)

Methods include:

- [defineNullableJsonField](#definenullablejsonfield)
- [updateNullableJsonField](#updatenullablejsonfield)

## getNullableJsonField

**Description:**

Nullable JSON field.

**Parameters:**

- None

**Returns:**

- (string)

**Example:**

```php
/**
 * @inheritDoc
 */
protected function getNullableJsonField(): string
{
    return 'meta';
}
```

## defineNullableJsonField

**Description:**

Define nullable JSON field array, removing keys with null values.

**Parameters:**

- `$array` (array)

**Returns:**

- (array)

**Throws:**

- (none)

## updateNullableJsonField

**Description:**

Update nullable JSON field array, merging updated array with existing and removing keys with null values.

**Parameters:**

- `$ormService` (`OrmService`)
- `$table_name` (string)
- `$primary_key_field` (string)
- `$primary_key` (mixed)
- `$meta_field` (string)
- `$array` (array)

**Returns:**

- (array)

**Throws:**

- (none)