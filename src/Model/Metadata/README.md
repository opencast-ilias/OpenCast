# Metadata

Events and Series are using a well-defined catalogue of Metadata fields (see https://docs.opencast.org/r/11.x/developer/#api/types/#metadata-catalogs).

In the plugin configuration you can configure which of these fields can be used in the plugin.
Each field configuration has the following parameters:
- ID: identifies the field, is defined by the catalogue
- German Title
- English Title
- visible for: either everyone or only for admins
- read-only: field can not be edited in a form. Note that some fields are defined to be read-only by Opencast, so they can't be configured editable in ILIAS.
- required: field is required in a form. Note that some fields are defined to be read-only by Opencast, so they can't be configured editable in ILIAS.
- prefilled: field is prefilled when creating an event or a series. Options are prefilled with current user's username or title of the course.

Note that read-only fields which are not prefilled are not visible in creation forms. Only in edit forms and in the table.

## Architecture

### Config
The configuration of metadata fields are accessible via the MDFieldConfigRepository (one each for event and series).

### Catalogue / Definition
A catalogue is a fixed set of Metadata field types, here called MDFieldDefinition. It is defined by Opencast.
The catalogues for events and series are accessible via MDCatalogueFactory.

An MDFieldDefinition consists of the following properties:
- ID: string
- type: MDDataType
- read_only: bool
  - this may not always correspond to the read-only status defined in Opencast. E.g. isPartOf is editable in Opencast 
    but not in the plugin (the series-id of an event is defined by the object context)
- required: bool

### Metadata / MetadataField
The actual data. There's a MetadataFactory to build (empty) Metadata containers for series and events.

### Additional Info
Note that the connection between config, definition and metadata field is via the ID/field_id.

All service classes (factories, repositories etc.) are accessible via the DI container (see [Dependency Injection](../../DI/README.md)).