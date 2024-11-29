# [ORM service](../README.md) > [Traits](README.md) > Castable

The `Bayfront\BonesService\Orm\Traits\Castable` trait can be used by any type of [model](../models/README.md) 
to cast fields to another type.

This is particularly useful within the [transform](../models/ormmodel.md#transform) method.

For example, if a `ResourceModel` contains a field stored as JSON, it may look something like:

```php
use Bayfront\BonesService\Orm\Models\ResourceModel;
use Bayfront\BonesService\Orm\Traits\Castable;

class Users extends ResourceModel
{

    use Castable;
    
    /**
     * Filter fields before writing to resource (creating and updating).
     *
     * @param array $fields
     * @return array
     * @throws UnexpectedException
     */
    protected function onWriting(array $fields): array
    {
        return $this->transform($fields, [
            'meta' => [$this, 'jsonEncode']
        ]);
    }

    /**
     * Filter fields after a resource is read.
     *
     * @param array $fields
     * @return array
     * @throws UnexpectedException
     */
    protected function onRead(array $fields): array
    {
        return $this->transform($fields, [
            'meta' => [$this, 'jsonDecode']
        ]);
    }

}
```

Methods include:

- [jsonEncode](#jsonencode)
- [jsonDecode](#jsondecode)
- [escapeString](#escapestring)
- [boolean](#boolean)
- [integer](#integer)
- [nullify](#nullify)
- [slug](#slug)
- [censor](#censor)
- [date](#date)
- [datetime](#datetime)
- [timestamp](#timestamp)
- [encrypt](#encrypt)
- [decrypt](#decrypt)

## jsonEncode

**Description:**

Cast array to JSON-encoded string.

**Parameters:**

- `$value` (mixed): Array to encode

**Returns:**

- `(string)`

## jsonDecode

**Description:**

Cast JSON-encoded string to array.

**Parameters:**

- `$value` (mixed): String to decode

**Returns:**

- `(array)`

## escapeString

**Description:**

Escape strings and arrays using UTF-8 character encoding.

**Parameters:**

- `$value` (mixed)

**Returns:**

- `(string)`

## boolean

**Description:**

Cast to boolean.

**Parameters:**

- `$value` (mixed)

**Returns:**

- `(bool)`

## integer

**Description:**

Cast to integer.

**Parameters:**

- `$value` (mixed)

**Returns:**

- `(int)`

## nullify

**Description:**

Cast empty string to `NULL`.

**Parameters:**

- `$value` (mixed)

**Returns:**

- `(mixed)`

## slug

**Description:**

Cast to lowercase + kebab case (URL-friendly slug).

**Parameters:**

- `$value` (mixed)

**Returns:**

- `(string)`

## censor

**Description:**

Cast non-null and non-empty values to censored string `********`.

**Parameters:**

- `$value` (mixed)

**Returns:**

- `(string|null)`

## date

**Description:**

Cast integer timestamp to date string.

**Parameters:**

- `$value` (mixed)

**Returns:**

- `(string)`: Date in `Y-m-d` format

## datetime

**Description:**

Cast integer timestamp to datetime string.

**Parameters:**

- `$value` (mixed)

**Returns:**

- `(string)`: Date in `Y-m-d H:i:s` format

## timestamp

**Description:**

Cast date/time string to timestamp.

**Parameters:**

- `$value` (mixed)

**Returns:**

- `(int)`

## encrypt

**Description:**

Cast to encrypted string using the Bones [app key](https://github.com/bayfrontmedia/bones/blob/master/docs/usage/config.md#key).

**Parameters:**

- `$value` (mixed)

**Returns:**

- `(string)`

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\InvalidFieldException`
- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`

## decrypt

**Description:**

Cast to decrypted value using the Bones [app key](https://github.com/bayfrontmedia/bones/blob/master/docs/usage/config.md#key).

**Parameters:**

- `$value` (mixed)

**Returns:**

- `(mixed)`

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\InvalidFieldException`
- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`