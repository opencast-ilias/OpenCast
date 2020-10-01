# Configuration
This document gives a short description to each available setting in the plugin configuration. It's structured in the same order as the plugin configuration's tabs and subtabs (from left to right).

## Settings
### API
##### API Version
Some functions are restricted to a certain API version. The version can be found by typing *https://[your-opencast-url]/api* in your browser.

##### API base URL
URL of your Opencast installation, followed by /api. E.g.: https://myopencast.com/api

##### API username
Login of the user which will be used to call the API. 

*Note that the API user must have enough permissions to access all API nodes. Therefore it should have all Roles beginning with 'ROLE_API_'.*

##### API password
Password for the above configured API user.

### Events
##### Processing Workflow ID
ID of the Workflow which will be started after uploading an event. 

##### Unpublish Workflow ID
In older versions of Opencast, the event's publications have to be retracted before deleting an event, otherwise the publications will remain somewhere in the system. If this is the case for the configured installation, enter the ID of the retracting workflow here.

##### Link to Opencast Video Editor
Link used for the action 'cut'. The placeholder {event_id} can be used. Default: https://[your-opencast-url.com]/admin-ng/index.html#!/events/events/{event_id}/tools/editor

##### Activate "Schedule Event(s)"
If active, admins will have the possibility to create scheduled events via an Opencast series in ILIAS.

*Warning: Scheduling events requires Opencast API v1.1.0.*

##### Enable Opencast Studio
Introduces a button "Opencast Studio" for users with the permission 'Upload' and/or 'Edit Videos'. This button redirects to Opencast Studio while configuring it to upload the event to the current series. Note that users will have to login in Opencast to use Opencast Studio.

*Opencast Studio is integrated in Opencast versions > 8.3*

##### Audio Files
Configures the Upload form to allow audio files. Note that the same workflow as for videos will be used, so this workflow has to be able to handle audio files.

##### Internal video player
This enables the usages of the plugin's integrated standalone Paella Player. Otherwise the 'Play' button will redirect to Opencast, which may require the user to login to Opencast.

In order for the player to find the video data, the 'Player' publication will have to be configured correctly (see chapter 'Publications').

The player can be configured to use streaming URLs. This requires a Wowza streaming server though.

##### Live Streams
Allow viewing Live Streams, optionally with an on-screen chat during the event. The publication "Live Stream" has to be configured properly for the plugin to recognize live events.

##### Open player in modal
When active: the video player will not be opened in a seperate window but in a overlaying "Modal" window.

##### Enable "Report Quality Problems"
If enabled, the "Actions" dropdown of events will offer the option "Report Quality Problems". This will open an overlay where the user can describe the problem and send it to the here configured email address.

##### Metadata of scheduled events editable
Defined whether scheduled events can be edited in ILIAS. 

### Series
##### EULA
"End User License Agreement", will be shown and has to be accepted when creating a new series.

##### Licenses
Configure licenses which will be shown as a dropdown input when creating a new series or editing a series' setting, respectively. The configured license of a series is purely informative.

Add new licenses in the form 'URL#Name', e.g.:

> http://creativecommons.org/licenses/by/2.5/ch/#CC: Attribution
> http://creativecommons.org/licenses/by-nc/2.5/ch/#CC: Attribution-Noncommercial

##### License info
This info will be shown below the 'Licenses' dropdown.

##### Enable "Report Date Modification"
If active and a series contains one or multiple scheduled events, the toolbar will show a button "Report Date Modifications" for admins. This button opens an overlay where the admin can describe the problem and send it to the here configured email address.

### Groups & Roles
##### ILIAS Producers
This is a group which must exist in Opencast. Users with the permission 'Edit Videos' will be enlisted in this group when a series is created or when the user performs the actions 'cut' or 'annotate'. This should grant these users the permission to access the Opencast video editor and annotation tool, so this group should contain enough roles to enable these tools.

##### Standard roles
A list of Opencast roles which will be added (with *read* and *write* permissions) to every event and series created by the plugin. This should not be necessary in a normal setup, if the api user has enough permissions.

##### Prefix for user roles
User specific role in Opencast, which is automatically created for each user. Is used by the plugin to grant the user permission on a series in Opencast. This will usually be *ROLE_USER_{IDENTIFIER}*, where {IDENTIFIER} will be replaced with the user's identifier (external account or email).

##### Prefix for owner role
Indicates the owner of an event. This rules doesn't have to exist in Opencast, although it will be set in Opencast. Example: ROLE_OWNER_{IDENTIFIER}

##### User mapping in uppercase
Will transform the user's identifier to all uppercase. Possibly necessary to match the user role. E.g. if the user's external account is 'jdoe', the user role will be ROLE_USER_JDOE instead of ROLE_USER_jdoe.

### Security
##### Sign * Link
The URLs for the player, download, thumbnail and annotation tool can be signed by Opencast, in order to make them available only for a certain period of time and optionally for a certain IP address. *This will only work if the url signing is configured in Opencast* (see https://docs.opencast.org/develop/admin/#configuration/stream-security/). 

The download links are not visible to the user, because the download is executed by the plugin. The plugin will then deliver the file to the user's browser. Therefore, the IP restriction is not available for download links.

##### Enable Annotation token security
*This function will only work with a certain version of the Annotation tool (https://github.com/mliradelc/annotation-tool/tree/uzk-ilias-frontend-hash).*

 Sends the course reference ID, and the user, admin or a student, as a hash to the annotation tool. With that information, the tool will verify if the user is coming from ILIAS and if it is the same user as the user logged in ILIAS. 

### Advanced

##### Common IdP
Check if ILIAS and Opencast are using the same identity provider (e.g. Shibboleth or LDAP). This allows for more precise permission checks: if a common IdP is used, the plugin can send the username to Opencast to check for permissions, so Opencast can validate all roles possessed by this user. Otherwise, the user very likely doesn't exist in Opencast, so the plugin can only send the user-specific role for permission checks. This is currently only used by the PageComponent plugin. 

##### User mapping
Defines which user attribute will be used to map an ILIAS user to an Opencast user (or user role, respectively). If your ILIAS and Opencast are both connected to the same Identity Provider (e.g. an LDAP server) this should be the 'External-ID', since ILIAS stores the ID coming from the IdP in this attribute.

##### Activate cache
Improves the performance by temporarily storing event metadata.

##### Debug level
Level of detail for log entries. The log can be found in ILIAS' external data directory (same place where the ilias.log is found) and is titled 'curl.log'.

##### Request combination level
Defines the way the plugin is sending requests to fetch events from Opencast (many small/few large requests). This has no impact on the functionality but may affect the performance.

##### Without metadata
The metadata don't need to be fetched seperately in the latest API versions, therefore this option will improve the performance. However, this will lead to an error in previous versions.

##### Upload chunk size
The video upload is separated in chunks, whereas one chunk has the here defined size. Increasing the chunk size can improve the upload speed. Default: 20MB

##### Upload via Ingest Nodes
If enabled, the upload will be executed via Ingest Nodes instead of the external API. This improves the load distribution on the Opencast server when uploading multiple files simultaneously. Note that the REST endpoint /ingest has to be available for the ILIAS server and the API user.

## Workflows
This view allows you to configure workflow definitions with optional workflow parameters. If any workflows are configured, the "Actions" menu of events will show a new option "Publish" which will open an overlay through which one of the configured workflows can be started for that event.

A workflow configuration requires the following fields:
* ID: a valid workflow definition ID
* Title: will be used in the overlay
* Parameters: a comma-separated list of workflow parameters. These parameters will be set to 'true' when starting the workflow.

## Workflow Parameters
### Parameters
Workflow Parameter are information which are passed to Opencast through the variable 'processing' when uploading or scheduling an event. Note that the parameters must match the given workflow definition (Settings -> Events -> Processing Workflow ID). Clicking the button "Load Parameters via API" will try to load the parameters automatically - however this will only work if the workflow definition in Opencast has configured a configuration panel.

The default parameters match the default workflow 'schedule-and-upload'.

### Settings
##### Enable configuration in series
If enabled, the configuration of parameters will also be available in the settings of an Opencast series. Otherwise the plugin settings will be effective globally.

## Publications
After an event is created, an Opencast workflow will process the event and create certain publications. These publications contain the links required by the plugin to enable essential functionalities, like the video player, thumbnails or download links. 

Therefore, for each of these functionalities, a publication has to be configured. Each publication is identified by a 'channel' and may contain media and attachments. Media and attachments can be identified by a flavor or tags.

So e.g. for the thumbnails to work, a publication of the usage 'thumbnail' has to be configured. The channel and flavor/tags must identify the correct publication and attachments to find the url of the thumbnails. Here's a list with a short description of what each publication usage is used for:

* **annotate**: renders button with a link to the Opencast annotation tool.
* **cutting**: fetches the url used for the action 'Cut'. Can be replaced by the configuration at *Settings* -> *Events* -> *Link to Opencast editor*.
* **download**: renders a button to download an event.
* **download_fallback**: fallback for the download publication.
* **live_event**: if this publication is found for an event, it will be considered a live event (only necessary if Live Streaming is active).
* **player**: renders the button to start the player. If the internal video player is active, this configuration should be of the type "Media", otherwise "Publication itself".
* **segments**: renders the segments for the paella player (only used for the internal video player).
* **preview**: renders the preview thumbnails for the paella player (only used for the internal video player).
* **thumbnail**: renders the thumbnails
* **thumbnail_fallback/thumbnail_fallback_2**: fallbacks for the thumbnails
* **video_portal**: renders a link to the external video portal (see chapter *Video Portal*). 
* **unprotected_link**: renders an unprotected link in the event list.
 
The default configuration (after a fresh install, or at /configuration/default_config.xml) contains publication configurations for all essential functions, that is the player, download, and thumbnails. Additionally, there are default values for the publications player, download, thumbnails, segments and preview, so these publications will use default values, even if they are not configured in the plugin configuration.

Note that the default configuration only works with an out-of-the-box Opencast. If there are different workflows which affect the publications, this configuration will have to be adjusted accordingly.

## Video Portal
Some institutions run an external video portal to which Opencast events will be published, based on the permissions set for events. This configuration allows to create permission templates, which can be chosen when creating a new series. 

### General
##### Title of external Video Portal
Will be shown when creating a new series and in the settings tab of an existing series.

##### Link to external Video Portal
Will be displayed in the settings and info tab of a series. {series_id} can be used as a placeholder. E.g.: https://myopencast-tube.com/cast/channels/{series_id}

### Permission Templates
The created templates can be chosen from when creating or editing a series. If chosen, the configured role with the given permissions and actions will be set on the series. The roles of the residual templates will be removed.

## Import/Export
Import or export the entire plugin configuration.

## Reports
An overview over all messages sent with the functions "Report Quality Problem" and "Report Date Modification".
