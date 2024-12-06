# [ORM service](../README.md) > FieldParser

The `Bayfront\BonesService\Orm\Utilities\Parsers\FieldParser` implements
[FieldParserInterface](fieldparserinterface.md), and can be used to identify the requested fields to use in
the [read](../models/resourcemodel.md#read) method of a `ResourceModel`.

The `FieldParser` is used to parse a query from an array.
An array is required to be passed to its constructor.

## Example

Example array syntax is shown below.

```php
$query = [
    'fields' => [ // Fields
        'id',
        'name'
    ]
];
```

To simplify sending queries via an HTTP request, fields can be defined as a comma-separated string.

For example, the above query sent via an HTTP request parameters could be:

```
?fields=id,name
```