# Model

The model is split up into Components. A Component..
- has one or multiple logically connected objects, which each represent either an object from the ILIAS DB
(via ActiveRecord, e.g. classes PluginConfig, PublicationUsage) or an object returned by the Opencast API (e.g. Event, Series)
- most should have a Repository Service Class with functions to handle the object persistence (e.g. CRUD)
  - there are some exceptions, e.g. Metadata is handled by the EventRepository or SeriesRepository, that's why there's
  no MetadataRepository
- may have other classes to help handling the data around the object(s) (e.g. Factories/Parsers etc.)
- Repositories and other service-like classes should be integrated in the [OpencastDIC](../Util/DI/README.md)

## Refactoring state
- Not all objects have repositories yet:
  - some ActiveRecord classes are still used directly (statically)
  - some object from the API are still subclasses of [ApiObject](./API/README.md)

Check each component's README for more details.