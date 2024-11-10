# [ORM service](README.md) > OrmService class

The `Bayfront\BonesService\Orm\OrmService` class contains the following Bones services:

- [EventService](https://github.com/bayfrontmedia/bones/blob/master/docs/services/events.md) as `$this->events`
- [FilterService](https://github.com/bayfrontmedia/bones/blob/master/docs/services/filters.md) as `$this->filters`
- [Db](https://github.com/bayfrontmedia/bones/blob/master/docs/services/db.md) as `$this->db`

Methods include:

- [getConfig](#getconfig)

## getConfig

**Description:**

Get ORM service configuration value in dot notation.

**Parameters:**

- `$key = ''` (string): Key to return in dot notation
- `$default = null` (mixed): Default value to return if not existing

**Returns:**

- (mixed)
