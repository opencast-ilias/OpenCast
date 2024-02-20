# Dependency Injection

All service classes should be added to the Dependency Injection Container
OpencastDIC. The container should only be
initialized by the GUI base classes, which extract the necessary services and
pass them on to the next GUI classes.

Therefore, the OpencastDIC has public methods for all classes needed by a GUI
class. Services only required by other
services don't have to be accessible via public method.
