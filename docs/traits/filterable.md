# [ORM service](../README.md) > [Traits](README.md) > Filterable

The `Bayfront\BonesService\Orm\Traits\Filterable` trait can be used by any type of [model](../models/README.md)
to filter field(s) before interacting with the database or being returned.

Methods include:

- [filterOnCreate](#filteroncreate)
- [filterOnRead](#filteronread)
- [filterOnUpdate](#filteronupdate)
- [filter](#filter)

## filterOnCreate

**Description:**

Filter fields on create.

When used with a [ResourceModel](../models/resourcemodel.md), 
the filter will be applied after all other processing has been completed, and just before the resource is created.

**Parameters:**

- `$fields` (array)

**Returns:**

- (array)

## filterOnRead

**Description:**

Filter fields when read.

When used with a [ResourceModel](../models/resourcemodel.md),
the filter will be applied after all other processing has been completed, and just before the resource is returned.

**Parameters:**

- `$fields` (array)

**Returns:**

- (array)

## filterOnUpdate

**Description:**

Filter fields when updated.

When used with a [ResourceModel](../models/resourcemodel.md),
the filter will be applied after all other processing has been completed, and just before the resource is updated.

**Parameters:**

- `$primary_key_id` (mixed)
- `$fields` (array)

**Returns:**

- (array)

## filter

**Description:**

Filter fields.

A generic method which can be used to filter fields.

**Parameters:**

- `$fields` (array)

**Returns:**

- (array)