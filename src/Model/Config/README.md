# PluginConfig

An Active Record subclass to manage persistence of the plugin configuration.

## Refactoring

This class is way too big and should refactor all non-CRUD functions in new classes. At least some kind of
repository should be introduced, especially if multi-(Opencast-)tenancy will be required, to simplify switching between
tenant configurations.
