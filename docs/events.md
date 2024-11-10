# [ORM service](README.md) > Events

The following [events](https://github.com/bayfrontmedia/bones/blob/master/docs/services/events.md) are added by this service:

- `orm.start`: Executes in the [OrmService](ormservice.md) constructor as the first event available to this service.
 The `OrmService` instance is passed as a parameter.
- `orm.model`: Executes when any [OrmModel](models/ormmodel.md) is instantiated. The model instance is passed as a parameter.
- `orm.resource.create`: Executes when a resource is created. An [OrmResource](ormresource.md) instance representing the created resource
 is passed as a parameter.
- `orm.resource.update`: Executes when a resource is updated. An [OrmResource](ormresource.md) instance representing the updated resource,
 an array representing the pre-updated resource, and an array representing the updated fields are passed as parameters.
- `orm.resource.trash`: Executes when a resource is soft-deleted. An [OrmResource](ormresource.md) instance representing the pre-deleted
 resource is passed as a parameter.
- `orm.resource.restore`: Executes when a resource is restored. An [OrmResource](ormresource.md) instance representing the restored
  resource is passed as a parameter.
- `orm.resource.delete`: Executes when a resource is hard-deleted. An [OrmResource](ormresource.md) instance representing the pre-deleted
  resource is passed as a parameter.