# [ORM service](../README.md) > QueryParser

The `Bayfront\BonesService\Orm\Utilities\Parsers\QueryParser` implements
[QueryParserInterface](queryparserinterface.md), and is used in the [list](../models/resourcemodel.md#list) 
method of a `ResourceModel`.

The `QueryParser` is used to parse a query from an array.
An array is required to be passed to its constructor.
This array is capable of being sent via an HTTP request URL query parameters as a JSON-encoded string.

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