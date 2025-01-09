# [ORM service](README.md) > OrmCollection class

The `Bayfront\BonesService\Orm\OrmCollection` class is used to represent a collection of resources.

It is returned by the [list](models/resourcemodel.md#list) method of a `ResourceModel`.

Methods include:

- [getModel](#getmodel)
- [getModelClassName](#getmodelclassname)
- [getPrimaryKey](#getprimarykey)
- [getCount](#getcount)
- [list](#list)
- [read](#read)
- [getPagination](#getpagination)
- [getAggregate](#getaggregate)
- [getTotalQueries](#gettotalqueries)
- [getQueryTime](#getquerytime)

## getModel

**Description:**

Get model instance of collection.

**Parameters:**

- (none)

**Returns:**

- [ResourceModel](models/resourcemodel.md)

## getModelClassName

**Description:**

Get fully namespaced class of collection.

**Parameters:**

- (none)

**Returns:**

- (string)

## getPrimaryKey

**Description:**

Get primary key field name of collection.

**Parameters:**

- (none)

**Returns:**

- (string)

## getCount

**Description:**

Get collection count.

**Parameters:**

- (none)

**Returns:**

- (int)

## list

**Description:**

List collection.

**Parameters:**

- (none)

**Returns:**

- (array)

## read

**Description:**

Read single resource from the collection.

**Parameters:**

- `$primary_key_id` (mixed)

**Returns:**

- (array)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\DoesNotExistException`

## getPagination

**Description:**

Get collection pagination.

This method queries the database.

When pagination type is `page`, the following array will be returned:

```php
[
    'results' => [
        'current' => 10, // Number of results returned with current collection (int)
        'total' => 100, // Total number of results existing
        'from' => 1, // First returned result (int) or NULL if none
        'to' => 10, // Last returned result (int) or NULL if none
    ],
    'page' => [
        'size' => 10, // Result limit (int)
        'current' => 1, // Current page number (int)
        'previous' => null, // Previous page number (int) or NULL if not existing
        'next' => 2, // Next page number (int) or NULL if not existing
        'total' => 10, // Total number of pages (int)
    ]
]
```

When pagination type is `cursor`, the following array will be returned:

```php
[
    'results' => [
        'current' => 10, // Number of results returned with current collection (int)
    ],
    'cursor' => [
        'first' => 'ENCODED_STRING', // Cursor of first returned resource
        'last' => 'ENCODED_STRING', // Cursor of last returned resource
    ]
]
```

**Parameters:**

- (none)

**Returns:**

- (array)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\InvalidRequestException`

## getAggregate

**Description:**

Get aggregate results from query.

This method queries the database once for each aggregate function.

The returned array will be keyed by column and function. For example:

```php
[
   '*' => [
       'COUNT' => 10,
    ],
    'charged_amount' => [
        'COUNT' => 3,
        'SUM' => 157.75,
    ]
]
```

**Parameters:**

- (none)

**Returns:**

- (array)

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\InvalidRequestException`

## getTotalQueries

**Description:**

Get total number of database queries.

**Parameters:**

- (none)

**Returns:**

- (int)

## getQueryTime

**Description:**

Get total time elapsed in seconds for all database queries.

**Parameters:**

- `$decimals = 3` (int)

**Returns:**

- (float)