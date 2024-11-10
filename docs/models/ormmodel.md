# [ORM service](../README.md) > [Models](README.md) > OrmModel

The `Bayfront\BonesService\Orm\Models\OrmModel` requires an [OrmService](../ormservice.md) instance to be passed 
to the constructor, and extends `Bayfront\Bones\Abstracts\Model`.

An `OrmModel` can be created with the `php bones make:ormmodel` [console command](README.md).

Configuration includes:

- [db_name](#db_name)
- [table_name](#table_name)

Methods include:

- [getDbName](#getdbname)
- [getTableName](#gettablename)
- [newQuery](#newquery)
- [createUuid](#createuuid)

## db_name

Database name, or blank for current.

See [getConnection](https://github.com/bayfrontmedia/simple-pdo/blob/master/docs/README.md#getconnection).

- Visibility: `protected`
- Type: `string`

## table_name

Table name.

- Visibility: `protected`
- Type: `string`

## getDbName

**Description:**

Get database name.

**Returns:**

- (string)

## getTableName

**Description:**

Get table name.

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