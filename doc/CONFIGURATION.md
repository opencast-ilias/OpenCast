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

*Note that the API user must have enough permissions to access all API nodes and to switch user roles. Therefore it should have all roles beginning with 'ROLE_API_', plus the role 'ROLE_SUDO'*

##### API password
Password for the above configured API user.

### Events
##### Processing Workflow ID
ID of the Workflow which will be started after uploading an event.

##### Unpublish Workflow ID
In older versions of Opencast, the event's publications have to be retracted before deleting an event, otherwise the publications will remain somewhere in the system. If this is the case for the configured installation, enter the ID of the retracting workflow here.

##### Link to Opencast Video Editor
Link used for the action 'cut'. The placeholder {event_id} can be used. Default: https://[your-opencast-url.com]/admin-ng/index.html#!/events/events/{event_id}/tools/editor

If this is empty, the Publication Usage for cutting will be used to fetch the link from Opencast (see [Publications](#Publications))

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

In order for the player to find the video data, the 'Player' publication will have to be configured correctly (see chapter [Publications](#Publications)).

The player can be configured to use streaming URLs. This requires a Wowza streaming server though.

##### Live Streams
Allow viewing Live Streams, optionally with an on-screen chat during the event. The publication "Live Stream" has to be configured properly for the plugin to recognize live events.

##### Use self-generated streaming URLs
If this is active, the plugin will generate streaming urls with the given 'Wowza URL', according to this pattern:

[WowzaURL]/smil:engage-player_[EventID]_[presenter|presentation].smil/playlist.m3u8

Otherwise, the urls will be fetched from the player publication. These can be static video urls or streaming urls, depending on how Opencast is configured.

##### Open player in modal
When active: the video player will not be opened in a seperate window but in a overlaying "Modal" window.

##### Enable "Report Quality Problems"
If enabled, the "Actions" dropdown of events will offer the option "Report Quality Problems". This will open an overlay where the user can describe the problem and send it to the here configured email address.

##### Enable "Report Date Modification"
If active and a series contains one or multiple scheduled events, the toolbar will show a button "Report Date Modifications" for admins. This button opens an overlay where the admin can describe the problem and send it to the here configured email address.

##### Metadata of scheduled events editable
Defined whether scheduled events can be edited in ILIAS.

### Paella Player
Define the basic configuration of plugin's Paella Player (config.json). You can chose from the default config ([located in the plugins code](./js/opencast/src/Paella/config/)),
an uploaded file, or a remote URL.

From paella player 7, the plugin uses player themes (by default opencast theme located in ./js/opencast/src/Paella/default_theme/opencast_theme.json) for videos on demand, and a specific livestream theme (located in ./js/opencast/src/Paella/default_theme/opencast_live_theme.json), these themes can also be replaced by a remote URL.

There is also the possibility to use a preview image as a fallback (located in ./templates/images/default_preview.png), in case opencast could not provide the video's default preview image somehow, this image can also be replaced by a remote URL.

Language Fallbacks of the paella player can also be set, which work in a form of fallback, because the user browser's language comes first. Order of the given fallbacks matters.
NOTE: the language files can also be extended or a new language file can also be added under (./js/opencast/src/Paella/lang) with .json format with key value pairs. Added language files must be registerd in (./js/opencast/src/Paella/lang/registery.js) just like the current added de language, in order for plugin to recognize the new language.

Default caption languages of the paella player can also be set in a form of fallback. the user browser's language takes the priority. The order of the given fallbacks also matter here.
NOTE: In order to captions to work, admin must configure the caption publication usages as well!
The plugin supports both ways of handling captions in opencast, namely attachments and media assets. To do that admin are also able to configure both of them at once by using caption fallback publication usage!

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

### Terms of Use
##### EULA
"End User License Agreement". Will be shown in objects in a separate tab 'Terms of Use'.

##### All users must accept the Terms of Use
If active, users will have to accept the ToU the first time they create an event.

##### Updated Terms of Use
If this is checked when saving the config form, all users which have already accepted the ToU are reset and will have
to accept it again on the next upload.

### Security
##### Sign * Link
The URLs for the player, download, thumbnail and annotation tool can be signed by Opencast, in order to make them available only for a certain period of time and optionally for a certain IP address. *This will only work if the url signing is configured in Opencast* (see https://docs.opencast.org/develop/admin/#configuration/stream-security/).

The download links are not visible to the user, because the download is executed by the plugin. The plugin will then deliver the file to the user's browser. Therefore, the IP restriction is not available for download links.

##### Enable Annotation token security
*This function will only work with a certain version of the Annotation tool (https://github.com/mliradelc/annotation-tool/tree/uzk-ilias-frontend-hash).*

 Sends the course reference ID, and the user, admin or a student, as a hash to the annotation tool. With that information, the tool will verify if the user is coming from ILIAS and if it is the same user as the user logged in ILIAS.

##### Presign (experimental)
If active, links will be presigned by Opencast. This may impact the performance. Note that the above configuration is still necessary because links will still have to be signed by the plugin in certain situations.

### Advanced

##### Common IdP
Check if ILIAS and Opencast are using the same identity provider (e.g. Shibboleth or LDAP). This allows for more precise permission checks: if a common IdP is used, the plugin can send the username to Opencast to check for permissions, so Opencast can validate all roles possessed by this user. Otherwise, the user very likely doesn't exist in Opencast, so the plugin can only send the user-specific role for permission checks. This is currently only used by the PageComponent plugin.

##### User mapping
Defines which user attribute will be used to map an ILIAS user to an Opencast user (or user role, respectively). If your ILIAS and Opencast are both connected to the same Identity Provider (e.g. an LDAP server) this should be the 'External-ID', since ILIAS stores the ID coming from the IdP in this attribute. If you want to use 'Email', make sure that users in ILIAS are not allowed to change their own email address.

##### Activate cache
Improves the performance by temporarily storing event metadata.

##### Debug level
Level of detail for log entries. The log can be found in ILIAS' external data directory (same place where the ilias.log is found) and is titled 'curl.log'.

##### Upload via Ingest Nodes
If enabled, the upload will be executed via Ingest Nodes instead of the external API. This improves the load distribution on the Opencast server when uploading multiple files simultaneously. Note that the REST endpoint /ingest has to be available for the ILIAS server and the API user.

## Workflows
This settings view allows you to configute the workflows that could be provided to the users via the "Start Workflow" button in the "Actions" menu of events in the event table/tile view. This sections consists of two sub-pages, one called "Settings" is responsible for general settings required to perform this feature, the other one is the list of  workflow definitions that are being presented to the users.

#### Settings
In this sub-section you need to define workflow tags that you would like to provide to the user. What it does, is simply getting the list of all workflows and then check if each workflow has any of your defined tags (which happens in the next sub-section).
NOTE: you can define a list of tags in a comma-separated format like: "api, archive, editor, delete, upload"

#### Workflow definition list
In this sub-section you are able to get the list of workflow definitions based on the tags you defined in the above sub-section, simply by clicking on the "Update workflow list" button. On the other hand, you are able to edit the title and description of each workflow definition based on you needs simply via "Actions / Edit", the delete button is also provided!

##### Configuration Panel
As a minor reminder and announcement, it is good to know that the configuration panel of each workflow definition (if any) will be dynamically and automatically displayed to the users and they can interact and enter values, which in return will be captured and sent to the workflow api as configuration parameter, therefore, you should take care of any cutom-defined logic that you provide for any workflow definition in Opencast.

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

## Metadata
The plugin uses a configurable selection of Opencast's [Metadata Catalogue](https://docs.opencast.org/r/11.x/developer/#api/types/#metadata-catalogs).
Note the subtabs for switching between event's and series' metadata configuration.

The configured Metadata fields will be visible in:
- the forms for creating or editing a series/an ILIAS object (series metadata)
- the forms for creating or editing an event (event metadata)
- the table containing events (event metadata)

A metadata field can be configured as:
- Visible for: either everyone or only for admins
- Read-only: field can not be edited in a form. Note that some fields are defined to be read-only by Opencast, so they can't be configured editable in ILIAS.
- Required: field is required in a form. Note that some fields are defined to be read-only by Opencast, so they can't be configured editable in ILIAS.
- Prefilled: field is prefilled when creating an event or a series. The prefilled text can be dynamically inserted as a form of text-base placeholder that admins can select from 3 different ilias global attributes as follows:
  - 1. COURSE: admins can tap COURSE object with provided properties such as ID, REF_ID, TITLE, etc.
  - 2. USER: admins can tap USER object with provided properties such as FIRSTNAME, LASTNAME, FULLNAME, etc.
  - 3. META: admins can tap into META object but limited only to 2 preperties including KEYWORDS, LANGUAGES.
The way of defining the prefilled placeholder follows a basic rule: it must be enclosed in square brackets "[]", all in CAPITAL letters, and narrowing down to properties by a "." (dot), for example: [USER.FIRSTNAME] or [COURSE.TYPE] or in case of META like [META.KEYWORDS.1]
It is also possible to define multiple placeholders in a single prefilled text option like: [USER.FIRSTNAME], [USER.LASTNAME]

#### Metadata + listproviders
In case a metadata field is a list and requires to get the its list of available values from Opencast, there is the a button provided for this feature when adding or editing the metadata called "Load values from API", which gets the values from opencast and converts them into the format expected by Possible values.
Opencast API user must have ROLE_API_LISTPROVIDERS_VIEW role which will be provided in latest version of Opencast (from 13 - 14)
In addition to the role, the API version must be set to 1.10.0 or above.

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
