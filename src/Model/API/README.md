# APIObject

The goal is to get rid of this class.

Before the refactoring, all object types that were loaded from the Opencast API were subclasses of APIObject. That means
they were responsible not only for holding data, but also for loading data from the API (and often many other things).

