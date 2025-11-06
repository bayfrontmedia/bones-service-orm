# ORM service

The ORM service is designed to help facilitate programmatically interacting with a database.

Although there may be some minimal overhead in using this ORM including the possibility of some additional database
queries beyond writing them individually, the ORM service typically eliminates any unexpected `PDO` exceptions
(i.e.: when creating or updating), and performs much of the validation and other repetitive tasks needed on data models.

## Exceptions

All exceptions thrown by the ORM service extend `Bayfront\BonesService\Orm\Exceptions\OrmServiceException`,
so you can choose to catch exceptions as narrowly or broadly as you like.

## Terminology

- **Models** represent an individual database table
- **Resources** represent a single row within a database table (model)
- **Fields** represent a single column of a single row (resource) within a database table (model)
- **Collections** represent multiple rows (resources) within a database table

## Documentation

- [Initial setup](setup.md)
- [Events](events.md)
- [Filters](filters.md)
- [OrmCollection](ormcollection.md)
- [OrmResource](ormresource.md)
- [OrmService](ormservice.md)
- [Models](models/README.md)
- [Traits](traits/README.md)
- [Utilities](utilities/README.md)