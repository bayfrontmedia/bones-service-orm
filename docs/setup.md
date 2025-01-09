# [ORM service](README.md) > Initial setup

- [Configuration](#configuration)
- [Add to container](#add-to-container)

## Configuration

This service requires a configuration array.
Typically, this would be placed at `config/orm.php`.

**Example:**

```php
return [
    'resource' => [
        'default_limit' => 100, // Default resource query limit
        'max_limit' => -1, // Default resource maximum limit allowed to query, or -1 for unlimited
        'max_related_depth' => 3, // Default resource maximum related field depth allowed to query
    ]
];
```
These values are used by the [ResourceModel](models/resourcemodel.md) class.

## Add to container

With the configuration completed, the [OrmService](ormservice.md) class needs to be added to the Bones [service container](https://github.com/bayfrontmedia/bones/blob/master/docs/usage/container.md).
This is typically done in the `resources/bootstrap.php` file.
You may also wish to create an alias.

For more information, see [Bones bootstrap documentation](https://github.com/bayfrontmedia/bones/blob/master/docs/usage/bootstrap.md).

**NOTE:** The ORM service requires the [Db service](https://github.com/bayfrontmedia/bones/blob/master/docs/services/db.md) to exist in the container.

To ensure it only gets instantiated when needed, the container can `set` the class:

```php
use Bayfront\Bones\Application\Utilities\App;

$container->set('Bayfront\BonesService\Orm\OrmService', function (Container $container) {

    return $container->make('Bayfront\BonesService\Orm\OrmService', [
        'config' => (array)App::getConfig('orm', [])
    ]);

});

$container->setAlias('ormService', 'Bayfront\BonesService\Orm\OrmService');
```

However, by allowing the container to `make` the class during bootstrapping,
the ORM service is available to be used in console commands:

```php
$ormService = $container->make('Bayfront\BonesService\Orm\OrmService', [
    'config' => (array)App::getConfig('orm', [])
]);

$container->set('Bayfront\BonesService\Orm\OrmService', $ormService);
$container->setAlias('ormService', 'Bayfront\BonesService\Orm\OrmService');
```