# [ORM service](../README.md) > [Models](README.md) > OrmModel

The `Bayfront\BonesService\Orm\Models\OrmModel` requires an [OrmService](../ormservice.md) instance to be passed 
to the constructor, and extends `Bayfront\Bones\Abstracts\Model`.

An `OrmModel` can be created with the `php bones make:ormmodel` [console command](README.md).

Configuration includes:

- [table_name](#table_name)

Methods include:

- [getTableName](#gettablename)
- [newQuery](#newquery)
- [createUuid](#createuuid)
- [transform](#transform)

## table_name

Table name.

- Visibility: `protected`
- Type: `string`

## getTableName

**Description:**

Get table name.

**Parameters:**

- (none)

**Returns:**

- (string)

## newQuery

**Description:**

Get new [Query](https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/query-builder.md) instance.

**Parameters:**

- (none)

**Returns:**

- `Query`

## createUuid

**Description:**

Create a lexicographically sortable UUID v7 string.

**Parameters:**

- (none)

**Returns:**

- (string)

## transform

**Description:**

Transform fields according to a defined rule.

For an example, see the [Castable](../traits/castable.md) trait.

**Parameters:**

- `$resource` (array): Array potentially containing fields to transform
- `$rules` (array): Set of rules as `$field => $callable`

**Returns:**

- (array): Transformed array

**Throws:**

- `Bayfront\BonesService\Orm\Exceptions\UnexpectedException`