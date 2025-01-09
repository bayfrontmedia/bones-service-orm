# [ORM service](README.md) > Events

The following [events](https://github.com/bayfrontmedia/bones/blob/master/docs/services/events.md) are added by this service:

- `orm.start`: Executes in the [OrmService](ormservice.md) constructor as the first event available to this service.
 The `OrmService` instance is passed as a parameter.
- `orm.model`: Executes when any [OrmModel](models/README.md) is instantiated. The model instance is passed as a parameter.
- `orm.resource.created`: Executes when a resource is created or upserted. An [OrmResource](ormresource.md) instance representing the created resource
 is passed as a parameter.
- `orm.resource.updated`: Executes when a resource is updated. An [OrmResource](ormresource.md) instance representing the updated resource,
 an `OrmResource` representing the pre-updated resource, and an array representing the updated fields are passed as parameters.
- `orm.resource.trashed`: Executes when a resource is soft-deleted. An [OrmResource](ormresource.md) instance representing the pre-deleted
 resource is passed as a parameter.
- `orm.resource.restored`: Executes when a resource is restored. An [OrmResource](ormresource.md) instance representing the restored
  resource and an `OrmResource` representing the pre-updated resource are passed as parameters.
- `orm.resource.deleted`: Executes when a resource is hard-deleted. An [OrmResource](ormresource.md) instance representing the pre-deleted
  resource is passed as a parameter.