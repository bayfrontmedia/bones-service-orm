# [ORM service](../README.md) > Models

There are two types of models which can be created: 

- [OrmModel](ormmodel.md): The simplest model instance available
- [ResourceModel](resourcemodel.md): Supports a variety of resource-specific functionality including CRUD actions.

Models can be created via the `make:ormmodel` [console command](https://github.com/bayfrontmedia/bones/blob/master/docs/usage/console.md).
Models will be placed in the `/Models` directory of the Bones application.

```shell
# Make OrmModel
php bones make:ormmodel ModelName

# Make ResourceModel
php bones make:ormmodel --type=resource ModelName
```

In addition, models can use [traits](../traits/README.md) to extend their functionality. 