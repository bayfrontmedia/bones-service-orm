# [ORM service](../README.md) > [Traits](README.md) > HasOmittedFields

The `Bayfront\BonesService\Orm\Traits\HasOmittedFields` trait can be used by any type of [model](../models/README.md) 
to define any fields containing potentially sensitive information to omit from external processing (i.e. logging).

While the trait itself has no bearing on the ORM service functionality, the included methods may be utilized 
in a variety of ways by the application.

Methods include:

- [getOmittedFields](#getomittedfields)
- [isOmittedField](#isomittedfield)

## getOmittedFields

**Description:**

Get omitted fields.

This method must be added to the model and return an array of fields which should be "omitted".

**Parameters:**

- (none)

**Returns:**

- (array)

**Example:**

```php
/**
 * @inheritDoc
 */
public function getOmittedFields(): array
{
    return [
        'password',
    ];
}
```

## isOmittedField

**Description:**

Is field omitted?

**Parameters:**

- `$field` (string)

**Returns:**

- (bool)