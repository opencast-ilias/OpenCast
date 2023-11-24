# UI

Should contain all UI related stuff: builders, factories, DTOs etc.

## Refactoring

Parts of the UI (mostly the ones in this namespace) are using the ILIAS UI
Service. That's why here are mostly
builders for tables and forms.

There are still some classes here that have not been refactored yet. Of course
not all components can be implemented
with the UI Service, but most still need some kind of refactoring; like
splitting the component class into a DTO-like
class and a Renderer.

Also, a lot of UI stuff is still hanging in the /classes dir (especially legacy
forms and tables). They should be moved
here eventually (and completely
and perfectly refactored, of course). The /classes dir should only contain
classes necessary for the ctrl flow.
