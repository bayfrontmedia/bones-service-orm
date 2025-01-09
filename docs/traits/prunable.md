# [ORM service](../README.md) > [Traits](README.md) > Prunable

The `Bayfront\BonesService\Orm\Traits\Prunable` trait can only be used by a [resourceModel](../models/resourcemodel.md)
to prune obsolete resources from the database.

Configuration includes:

- [getPruneField](#getprunefield)

Methods include:

- [prune](#prune)
- [pruneQuietly](#prunequietly)

## getPruneField

**Description:**

Datetime field used to prune resources.

**Parameters:**

- (none)

**Returns:**

- (string)

**Example:**

```php
/**
 * @inheritDoc
 */
protected function getPruneField(): array
{
    return 'created_at';
}
```

## prune

**Description:**

Delete resources older than timestamp.

The `orm.resource.deleted` [event](../events.md) is executed for each deleted resource.

NOTE: Since this method utilizes the model's [delete](../models/resourcemodel.md#delete) method,
models with [SoftDeletes](softdeletes.md) trait will be soft-deleted.

**Parameters:**

- `$timestamp` (int)

**Returns:**

- (void)

## pruneQuietly

**Description:**

Quietly hard-delete resources older than timestamp.

All deletes are performed in the same database query, 
so the `orm.resource.deleted` [event](../events.md) is not executed.

**Parameters:**

- `$timestamp` (int)

**Returns:**

- (void)