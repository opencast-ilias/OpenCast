##Internal API

####Description
The internal API contains CRUD-Methods (create, read, update, delete) for opencast events and series/objects. It aims to offer a simple way to 
interact with Opencast objects, without knowing much about their structure or which fields are stored in ILIAS and which in Opencast.

####Usage
#####Basic
Every call starts either with a `xoctInternalAPI::getInstance()->series()` or with a `xoctInternalAPI::getInstance()->events()` depending on which kind of objects should be handled. 

These calls return instances of the classes `xoctSeriesAPI` or `xoctEventAPI`, respectively, but never call these classes directly, since the function `xoctInternalAPI::getInstance()` initializes the API settings needed to interact with the Opencast API.

#####Series


#####Events