# [ORM service](../README.md) > FieldParserInterface

The `Bayfront\BonesService\Orm\Interfaces\FieldParserInterface` can be used to parse a request in order to
identify the requested fields to be returned in the [read](../models/resourcemodel.md#read) method of a `ResourceModel`.

Although you are free to create a class of your own, the ORM service has included a [FieldParser](fieldparser.md).

Methods include:

- [getFields](#getfields)

## getFields

**Description:**

Get fields to be returned. The actual field names must be used.

**Parameters:**

- (none)

**Returns:**

- (array)

**Examples:**

```php
['id','name'];
```