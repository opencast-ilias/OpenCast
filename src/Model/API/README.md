# APIObject

The goal is to get rid of this class.

Before the refactoring, all object types that were loaded from the Opencast API were subclasses of APIObject. That means
they were responsible not only for holding data, but also for loading data from the API (and often many other things).

After the refactoring, all object types should be split into a Data Class (more or less, can still use ActiveRecord for 
convenience) and a Repository Service handling the DataClass, to avoid huge and almighty classes.