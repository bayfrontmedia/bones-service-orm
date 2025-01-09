# [ORM service](../README.md) > QueryParserInterface

The `Bayfront\BonesService\Orm\Interfaces\QueryParserInterface` is used to parse a request in order to 
build the database query used in the [list](../models/resourcemodel.md#list) method of a `ResourceModel`.

The `ResourceModel` database query is built using the [Simple PDO query builder](https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md).

Although you are free to create a class of your own, the ORM service has included a [QueryParser](queryparser.md).

Methods include:

- [getFields](#getfields)
- [getFilter](#getfilter)
- [getSearch](#getsearch)
- [getSort](#getsort)
- [getGroup](#getgroup)
- [getLimit](#getlimit)
- [getPaginationMethod](#getpaginationmethod)
- [getPage](#getpage)
- [getBeforeCursor](#getbeforecursor)
- [getAfterCursor](#getaftercursor)
- [getAggregate](#getaggregate)
- [getPagination](#getpagination)

## getFields

**Description:**

Get fields to be returned in dot notation.

See [select](https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md#select).

**Parameters:**

- (none)

**Returns:**

- (array)

**Examples:**

```php
['*']; // All readable fields for the current model
['*.*.*']; // All readable fields up to 3 levels deep
['*','owner.id','owner.name']; // All readable fields for the current model, and id and name for the related owner field
['id','name','meta->address_city']; // id, name and address_city value of the meta JSON field
```

## getFilter

**Description:**

Get fields to filter by.

See [where](https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md#where) and [startGroup](https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md#startgroup).

In addition to hard-coded values, the ORM service supports the following dynamic variables via the [orm.query.filter](../filters.md):

- `$TIME(<adjustment>)`: The current timestamp plus/minus a given interval.
- `$DATETIME(<adjustment>)`: The current datetime in `Y-m-d H:i:s` format plus/minus a given interval.
- `$DATE(<adjustment>)`: The current date in `Y-m-d` format plus/minus a given interval.
- `$TIME`: The current timestamp.
- `$DATETIME`: The current datetime in `Y-m-d H:i:s` format.
- `$DATE`: The current date in `Y-m-d` format.

Adjustments can be any valid datetime used by PHP's [strtotime](https://www.php.net/manual/en/function.strtotime.php) function.

**Parameters:**

- (none)

**Returns:**

- (array)

**Examples:**

The top level filter must be an array of arrays.
Filter rules must be an array as `COLUMN => [RULE => VALUE]`.
Filter rules can be grouped by placing them within an array keyed as `_and` or `_or`.

Example 1:

```php
$filter = [
    [
        'name' => [
            'sw' => 'N'
        ],
        '_or' => [
            [
                '_and' => [
                    [
                        'name' => [
                            'sw' => 'S'
                        ]
                    ],
                    [
                        'description' => [
                            '!null' => true
                        ]
                    ]
                ]
            ]
        ]
    ]
];
```

The above example will filter the collection by results whose `name` starts with `N`,
OR those whose `name` starts with `S` and whose `description` is not `NULL`.

Example 2:

```php
$filter = [
	[
	    'last_name' => [
	        'eq' => 'Johnson'
	    ]
	],
	[
	    '_or' => [
	        [
	            '_and' => [
	                [
	                    'last_name' => [
	                        'eq' => 'Anderson'
	                    ],
	                ],
	                [
	                    'state' => [
	                        'eq' => 'California'
	                    ]
	                ]
	            ]
	        ]
	    ]
	],
	[
	    '_or' => [
	        [
	            '_and' => [
	                [
	                    'last_name' => [
	                        'eq' => 'Smith'
	                    ],
	                ],
	                [
	                    'status' => [
	                        'eq' => 'Active'
	                    ],
	                ],
	                [
	                    'state' => [
	                        'eq' => 'Florida'
	                    ]
	                ]
	            ]
	        ]
	    ]
	]
];
```

The above example will filter the collection by results whose `last_name` equals `Johnson`,
OR those whose `last_name` equals `Anderson` and whose `state` equals `California`
OR those whose `last_name` equals `Smith` and whose `status` equals `Active` and `state` equals `Florida`.

Building this query using the [Query class](https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md) directly would look like:

```php
$query->where('last_name', 'eq', 'Johnson')
    ->startGroup('OR')
    ->where('last_name', 'eq', 'Anderson')
    ->where('state', 'eq', 'California')
    ->endGroup()
    ->startGroup('OR')
    ->where('last_name', 'eq', 'Smith')
    ->where('status', 'eq', 'Active')
    ->where('state', 'eq', 'Florida')
    ->endGroup();
```

Example 3:

```php
$filter = [
    [
        'created_at' => [
            'gt' => '$DATE(- 1 year)'
        ]
    ],
    [
        '_or' => [
            [
                'description' => [
                    'null' => true
                ]
            ]
        ]
    ]
];
```

The above example will filter the collection by those whose `created_at` date is less than one year ago
OR whose `description` is `NULL`.

## getSearch

**Description:**

Get case-insensitive search string.

**Parameters:**

- (none)

**Returns:**

- (string)

## getSort

**Description:**

Get fields to sort/order by.

Values without a prefix or prefixed with a `+` will be ordered ascending.
Values prefixed with a `-` will be ordered descending.

See [orderBy](https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md#orderby).

**Parameters:**

- (none)

**Returns:**

- (array)

**Example:**

```php
['-name']; // Search by name descending
['name','-created_at']; // Search by name ascending and created_at descending
```

## getGroup

**Description:**

Get fields to group by.

See: [groupBy](https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md#groupby).

**Parameters:**

- (none)

**Returns:**

- (array)

**Example:**

```php
['department']; // Group by department
```

## getLimit

**Description:**

Get limit.

See [limit](https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md#limit).

**Parameters:**

- (none)

**Returns:**

- (int|null): `-1` for maximum allowed or `NULL` for the model's default limit

## getPaginationMethod

**Description:**

Get pagination method. One of:

- `QueryParserInterface::PAGINATION_PAGE`
- `QueryParserInterface::PAGINATION_BEFORE`
- `QueryParserInterface::PAGINATION_AFTER`

**Parameters:**

- (none)

**Returns:**

- (string)

## getPage

**Description:**

Get page.

**Parameters:**

- (none)

**Returns:**

- (int)

## getBeforeCursor

**Description:**

Get before cursor.

**Parameters:**

- (none)

**Returns:**

- (string)

## getAfterCursor

**Description:**

getAfterCursor

**Parameters:**

- (none)

**Returns:**

- (string)

## getAggregate

**Description:**

Get aggregate functions. 

The array keys must be an array containing one or more aggregate arrays.
Aggregate arrays must be an array as `FUNCTION => COLUMN`.

See [aggregate functions](https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md#aggregate).

**Parameters:**

- (none)

**Returns:**

- (array)

**Example:**

```php
[
    Query::AGGREGATE_COUNT => '*',
],
[
    Query::AGGREGATE_COUNT => 'charged_amount',
],
[
    Query::AGGREGATE_SUM => 'charged_amount',
];
```

## getPagination

**Description:**

Get pagination type. One of:

- `QueryParserInterface::PAGINATION_PAGE`
- `QueryParserInterface::PAGINATION_CURSOR`

**Parameters:**

- (none)

**Returns:**

- (string)

