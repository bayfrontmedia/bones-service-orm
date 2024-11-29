# [ORM service](../README.md) > [Traits](README.md) > SoftDeletes

The `Bayfront\BonesService\Orm\Traits\SoftDeletes` trait can only be used by a [resourceModel](../models/resourcemodel.md)
to soft-delete resources from the database.

When resources are soft-deleted, they still remain in the database 
and will be marked with the time the resources was deleted.
Soft-deleted resources will not be returned without using the `withTrashed` or `onlyTrashed` methods.
This includes soft-deleted related resources returned with the [list](../models/resourcemodel.md#list) method.

**NOTE:** A soft-deleted foreign key will still be returned as part of a non-deleted resource.
It will only be omitted if traversing to the level of the related resource.

Configuration includes:

- [getDeletedAtField](#getdeletedatfield)

Methods include:

- [withTrashed](#withtrashed)
- [onlyTrashed](#onlytrashed)
- [restore](#restore)
- [hardDelete](#harddelete)
- [purgeTrashed](#purgetrashed)
- [purgeTrashedQuietly](#purgetrashedquietly)
- [onTrashed](#ontrashed)
- [onRestoring](#onrestoring)
- [onRestored](#onrestored)

## getDeletedAtField

**Description:**

Datetime field used to save the deleted at date.

**Parameters:**

- (none)

**Returns:**

- (string)

**Example:**

```php
/**
 * @inheritDoc
 */
protected function getDeletedAtField(): string
{
    return 'deleted_at';
}
```

## withTrashed

**Description:**

Filter the next query to include soft-deleted resources.

NOTE: This method has no effect on methods within another trait.

**Parameters:**

- (none)

**Returns:**

- (self)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`

**Example:**

```php
// Read resource with primary key value of "123", even if soft-deleted
$resourceModel->withTrashed()->read('123');
```

## onlyTrashed

**Description:**

Filter the next query to include only soft-deleted resources.

NOTE: This method has no effect on methods within another trait.

**Parameters:**

- (none)

**Returns:**

- (self)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`

**Example:**

```php
// Read resource with primary key value of "123" only if it is soft-deleted
$resourceModel->onlyTrashed()->read('123');

// Do something only if resource is soft-deleted
if ($resourceModel->onlyTrashed()->exists('123')) {
    // Do something
}
```

## restore

**Description:**

Restore soft-deleted resource.

If successful, the `orm.resource.restored` [event](../events.md) is executed.

**Parameters:**

- `$primary_key_id` (mixed)

**Returns:**

- [OrmResource](../ormresource.md)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\DoesNotExistException`
- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`

## hardDelete

**Description:**

Delete a single existing or soft-deleted resource.

If successful, the `orm.resource.deleted` [event](../events.md) is executed.

**Parameters:**

- `$primary_key_id` (mixed)

**Returns:**

- (bool)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`

## purgeTrashed

**Description:**

Hard-delete all soft-deleted resources deleted before a given timestamp.

Deleting events are executed for each deleted resource.

**Parameters:**

- `$timestamp` (int)

**Returns:**

- (void)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`

## purgeTrashedQuietly

**Description:**

Quietly hard-delete all soft-deleted resources deleted before a given timestamp.

All deletes are performed in the same database query, so deleting actions and events are not executed.

**Parameters:**

- `$timestamp` (int)

**Returns:**

- (void)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`

## onTrashed

**Description:**

Actions to perform after a resource has been soft-deleted.

**Parameters:**

- `$resource` [OrmResource](../ormresource.md)

**Returns:**

- (void)

## onRestoring

**Description:**

Actions to perform before a resource is restored.

**Parameters:**

- `$resource` [OrmResource](../ormresource.md)

**Returns:**

- (void)

## onRestored

**Description:**

Actions to perform after a resource has been restored.

**Parameters:**

- `$resource` [OrmResource](../ormresource.md): Newly updated resource
- `$previous` [OrmResource](../ormresource.md): Previously existing resource

**Returns:**

- (void)