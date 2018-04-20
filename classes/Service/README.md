#Internal API

##Description
The internal API contains CRUD-Methods (create, read, update, delete) for opencast events and series/objects. It aims to offer a simple way to 
interact with Opencast objects, without knowing much about their structure or which fields are stored in ILIAS and which in Opencast.

##Usage
###Basic
Every call starts either with a `xoctInternalAPI::getInstance()->series()` or with a `xoctInternalAPI::getInstance()->events()` depending on which kind of objects should be handled. 

These calls return instances of the classes `xoctSeriesAPI` or `xoctEventAPI`, respectively, but: never call these classes directly! The function `xoctInternalAPI::getInstance()` initializes the API settings needed to interact with the Opencast API.

##Series
The SeriesAPI always handles both, the ILIAS object and the Opencast series and decides on it's own which data has to be changed where. However, the ILIAS reference ID is required as an identifier to find the objects and not the Opencast series ID, since multiple ILIAS objects can reference one Opencast series.

####create
*Parameters*: 
* $parent_ref_id (integer): ILIAS reference ID of the container in which the series should be created. If this id does not belong to a container, or the container is not a course/group or a subobject of such, an exception will be thrown.
* $title (String): This will be the title of the series, in ILIAS as well as in Opencast.
* $additional_data (array): Optional list of additional fields for the created series. The following fields are accepted:
	* owner (Integer): ILIAS user id which will be set as the object's owner. This user will also receive producers rights on this series in Opencast.
	* series_id (String): The ID of an existing Opencast series can be given here. Thus, the created object will reference this series and no new series will be created in Opencast.
	* description (String): The description will be set in the ILIAS object and in the Opencast series.
	* online (bool): Defines whether the ILIAS object will be online. This has no effect in Opencast. The default is false (offline).
	* introduction_text (String): This text will be shown inside the ILIAS object, but has no effect in Opencast.
	* license (String): The License will be set in the ILIAS object and in the Opencast series. This is often an URL to a creativecommons license.
	* use_annotations (bool): Defines whether the annotation tool is activated for this series. This has no effect in Opencast. The default is false.
	* streaming_only (bool): Defines whether the series should support streaming only, which means the download will be deactivated. This has no effect in Opencast. The default is false.
	* permission_per_clip (bool): Defines, whether the viewing permissions can be set for each event individually. This has no effect in Opencast. The default is false.
	* permission_allow_set_own (bool): Defines, whether the owner of an event can grant viewing access to other users. Only effective, if "permission_per_clip" is active, too. This has no effect in Opencast. The default is false.
	* member_upload (bool): Defines, whether the course/group member role obtains the permission "upload". This has no effect in Opencast. The default is false.

*Response*:

If no exception is thrown, the series was created successfully and an object of type *xoctOpenCast* is returned. The *xoctOpenCast* object contains metadata of the series, as well as a reference to the corresponding *ilObjOpenCast* (->getILIASObject()) and *xoctSeries* (->getSeries()) objects.

*Examples*:

Create a series with a few options in parent directory with reference id 83 and fetch it's series_id and it's ref_id: \
`$additional_data = array('online' => true, 'license' => 'http://creativecommons.org/licenses/by/2.5/ch', 'streaming_only' => true);` \
`$xoctOpencast = xoctInternalAPI::getInstance()->series()->create(83, 'Lecture Recordings 001', $additional_data);`\
`$series_id = $xoctOpencast->getSeries()->getIdentifier();`
`$ref_id = $xoctOpencast->getILIASObject()->getRefID();`

####read
*Parameters*:
* $ref_id (integer): Reference ID of the ILIAS object to be read.

*Response*:

An object of type *xoctOpenCast*. The *xoctOpenCast* object contains metadata of the series, as well as a reference to the corresponding *ilObjOpenCast* (->getILIASObject()) and *xoctSeries* (->getSeries()) objects.

*Examples*:

`$xoctOpencast = xoctInternalAPI::getInstance()->series()->read(172);`\
`$use_annotations = $xoctOpencast->getUseAnnotations();`\
`$series_id = $xoctOpencast->getSeries()->getIdentifier();`

####update

*Parameters*: 
* $ref_id (integer): Reference ID of the ILIAS object to be updated
* $data (array): List of fields to be updated. Every field is optional. The following fields are accepted:
	* title (String): The title will be set in ILIAS and in Opencast
    * description (String): The description will be set in the ILIAS object and in the Opencast series.
    * online (bool): Defines whether the ILIAS object will be online. This has no effect in Opencast.
    * introduction_text (String): This text will be shown inside the ILIAS object, but has no effect in Opencast.
    * license (String): The License will be set in the ILIAS object and in the Opencast series. This is often an URL to a creativecommons license.
    * use_annotations (bool): Defines whether the annotation tool is activated for this series. This has no effect in Opencast.
    * streaming_only (bool): Defines whether the series should support streaming only, which means the download will be deactivated. This has no effect in Opencast.
    * permission_per_clip (bool): Defines, whether the viewing permissions can be set for each event individually. This has no effect in Opencast.
	* permission_allow_set_own (bool): Defines, whether the owner of an event can grant viewing access to other users. Only effective, if "permission_per_clip" is active, too. This has no effect in Opencast.
    * member_upload (bool): Defines, whether the course/group member role obtains the permission "upload". This has no effect in Opencast. The default is false.

*Response*:

If no exception is thrown, the object was updated successfully and an object of type *xoctOpenCast* is returned. The *xoctOpenCast* object contains metadata of the series, as well as a reference to the corresponding *ilObjOpenCast* (->getILIASObject()) and *xoctSeries* (->getSeries()) objects.

*Examples*: 

Set object with ref_id 172 online:\
`xoctInternalAPI::getInstance()->series()->update(172, array('online' => true)); // set object with ref_id 172 online`

Change title and description of object with ref_id 183:\
`xoctInternalAPI::getInstance()->series()->update(183, array('title' => 'Lectures 01', 'description' => 'Lecture Recordings'));`

####delete

*Parameters*:
* $ref_id (integer): reference ID of the ILIAS object to be deleted.
* $delete_opencast_series (bool): flag, defining whether the corresponding series in Opencast should be deleted as well

*Response*:

None. If the object is not found, an exception is thrown.

*Examples*:

Delete object with ref_id 183 including opencast series:\
`xoctInternalAPI::getInstance()->series()->delete(183, true);`

##Events
Since no information about an event is stored in ILIAS (except for online/offline), the events do not have something like an ILIAS ID. Therefore, the identifier used for the following actions is the Opencast unique event identifier.

All data is stored in Opencast, except for an online/offline flag.

####create
This method only creates single scheduled events.

*Parameters*
* $series_id (String): Opencast's unique series identifier of the series, which this event should be part of.
* $title (String): Representing title of the event. 
* $start (DateTime): Start date and time.
* $end (DateTime): End date and time.
* $location (String): ID of the recording agent. Get a list of all recording agents with `xoctAgent::getAllAgents();`.
* $additional_data (String): Optional list of additional fields for the created series. The following fields are accepted:
	* description (String): Event description, in ILIAS shown as "Subtitle".
    * presenters (String): Presenting person(s).
    
*Response*:

If no exception is thrown, the event was created successfully and an object of type *xoctEvent* is returned.

*Examples*:

`$start = new DateTime('2018-06-01 14:00:00');`\
`$end = new DateTime('2018-06-01 14:30:00');`\
`$additional_data = array('presenters' => 'Prof. Farnsworth, Prof. Wernstrom');`\
`xoctInternalAPI::getInstance()->events()->create('8919734f-9c56-454f-8025-4604c3cca87b', 'Lecture 07', $start, $end, 'building_A_room_01', $additional_data);`


####read
*Parameters*:
* event_id (String): Opencast unique event identifier

*Response*: 

Object of type xoctEvent.

*Examples*:

`$xoctEvent = xoctInternalAPI::getInstance()->events()->read('8192f3-2183cb-123l');`\
`$title = $xoctEvent->getTitle();`\
`$series_id = $xoctEvent->getSeriesIdentifier();`


####update
*Parameters*:
* event_id (String): Opencast unique event identifier
* data (array): List of fields to be updated. Every field is optional. The following fields are accepted:
	* title (String): Representing title of the event.
	* start (DateTime): Start date and time
	* end (DateTime): Start date and time
	* location (String): ID of the recording agent. Get a list of all recording agents with `xoctAgent::getAllAgents();`.
	* description (String): Event description, in ILIAS shown as "Subtitle".
	* presenters (String): Presenting person(s).

*Response*:

If no exception is thrown, the event was updated successfully and an object of type *xoctEvent* is returned.

*Examples*:

Set event online:\
`$xoctEvent = xoctInternalAPI::getInstance()->events()->update('8192f3-2183cb-123l', array('online' => true));`

Change start and end date:\
`$start = new DateTime('2018-06-02 15:00:00');`\
`$end = new DateTime('2018-06-02 16:30:00');`\
`$xoctEvent = xoctInternalAPI::getInstance()->events()->update('8192f3-2183cb-123l', array('start' => $start, 'end' => $end));`

####delete
*Parameters*
* event_id (String): Opencast unique event identifier

*Response*

None. If the object is not found, an exception is thrown.

*Examples*:

`$deleted = xoctInternalAPI::getInstance()->events()->delete('8192f3-2183cb-123l');`
