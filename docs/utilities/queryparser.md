# [ORM service](../README.md) > QueryParser

The `Bayfront\BonesService\Orm\Utilities\Parsers\QueryParser` implements
[QueryParserInterface](queryparserinterface.md), and is used in the [list](../models/resourcemodel.md#list) 
method of a `ResourceModel`.

The `QueryParser` is used to parse a query from an array.
An array is required to be passed to its constructor.

## Example

Example array syntax is shown below. 

```php
$query = [
    'fields' => [ // Fields
        'name',
        'description',
        'tenant.*',
        'tenant.owner.meta->name_first',
        'tenant.owner.email',
    ],
    'filter' => [ // Filter
        [
            'created_at' => [
                'gt' => '$DATE(- 1 year)',
            ]
        ],
        [
            '_or' => [
                [
                    'description' => [
                        'null' => true,
                    ]
                ]
            ]
        ]
    ],
    'search' => 'term', // Case-insensitive search
    'sort' => [ // Sort by
        '-name',
    ],
    'group' => [ // Group by
        'tenant',
    ],
    'limit' => 2, // Limit
    'pagination' => 'page', // Pagination type
    'page' => 2, // Pagination method (use only one)
    'before' => 'ENCODED_STRING', // Pagination method (use only one)
    'after' => 'ENCODED_STRING', // Pagination method (use only one)
    'aggregate' => [ // Aggregate functions
        [
            'COUNT' => '*',
        ],
        [
            'SUM' => 'charged_amount',
        ]
    ],
];
```

An example querying a model for the fields `id`, `email` and `created_at` whose `created_at` field is within the past year:

```php
$query = $users->list(new QueryParser([
    'fields' => [
        'id',
        'email',
        'created_at'
    ],
    'filter' => [
        [
            'created_at' => [
                'gt' => '$DATETIME(-1 year)'
            ]
        ]
    ]
]));

$results = $query->list();
```

**Note:** Filters can be applied up to 2 levels deep as long as a field in the same related table is also being selected.
For example, if selecting the field `contact.name`, a filter can be applied to any field on the `contact` table.

To simplify sending queries via an HTTP request, 
all single dimensional arrays (`fields`, `sort` and `group`) can be defined as a comma-separated string.
Also, all multidimensional arrays (`filter` and `aggregate`) can be defined as a JSON-encoded string.

For example, the above query sent via an HTTP request parameters could be: 

```
?fields=id,email,created_at&filter=[{"created_at":{"gt":"$DATETIME(-1 year)"}}]
```