# [ORM service](README.md) > OrmResource class

The `Bayfront\BonesService\Orm\OrmResource` class is used to represent a single resource.

It is typically returned by various [ResourceModel](models/resourcemodel.md) methods as well as [events](events.md).

Methods include:

- [getModel](#getmodel)
- [getModelClassName](#getmodelclassname)
- [getPrimaryKey](#getprimarykey)
- [asObject](#asobject)
- [read](#read)
- [get](#get)

## getModel

**Description:**

Get model instance of resource.

**Parameters:**

- (none)

**Returns:**

- [ResourceModel](models/resourcemodel.md)

## getModelClassName

**Description:**

Get fully namespaced class of resource.

**Parameters:**

- (none)

**Returns:**

- (string)

## getPrimaryKey

**Description:**

Get primary key field value.

**Parameters:**

- (none)

**Returns:**

- (mixed)

## asObject

**Description:**

Get entire resource as an object.

**Parameters:**

- (none)

**Returns:**

- (object)

## read

**Description:**

Get entire resource as an array.

**Parameters:**

- (none)

**Returns:**

- (array)

## get

**Description:**

Get resource array key in dot notation.

**Parameters:**

- `$key` (string): Key to return in dot notation
- `$default = null` (mixed): Default value to return

**Returns:**

- (mixed)