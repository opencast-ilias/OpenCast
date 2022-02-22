# Change Log

## [4.0.0]
- Feature: Completely configurable metadata fields for events and series, used in forms and tables. See Configuration Tab "Metadata".
- Feature: Accept Terms of Use once per User, when creating an Event. See Configuration at "General" -> "Series".
- Feature: Setting for using custom paella player configs (upload or from URL) at object level. See object settings.
- Improvement: Quality & Date reports now set the senders email as ReplyTo address.
- Dependencies: Bumped npm packages, removed a few (hopefully) unused
- Refactoring: Refactored a big part of the model: moved to /src, introduced namespaces, dependency injection, repository patterns, etc.
- Doc: Added READMEs to some components

## [3.7.4]
- Fix: disable submit button in modals after submit
- Change: adjusted lang vars for 'Streaming only' and 'Live Chat' settings

## [3.7.3]
- Feature/Improvement: support fetching streaming urls from publications instead of building them half-statically
- Change/Fix: stop setting 1234 as default theme when creating a series (no theme is set anymore) 

## [3.7.2]
- Fix/Improvement: prevent multiple loading of metadata, improving page component performance
- Change: use paella player tag 6.5.5 instead of develop branch

## [3.7.1]
- Fix: fixed player bug by using paella player v6.5.5

## [3.7.0]
- Feature: Caching via ILIAS Database (configurable in Plugin configuration: Settings -> Advanced)
- Fix: live streams didn't work when streaming server redirected
- Change: added warning to owner role prefix (thanks to reiferschris)
- Change: bumped path-parse version to 1.0.7
- Fix: Fixed HLS stream when "Annotation T Sec" is enabled (thanks to mliradelc)

## [3.6.0]
- Change: ILIAS 7 compatibility
- Feature: internal series api - allow additional producers when creating series
- Feature: internal event api - allow setting workflow parameters when creating event
- Feature: paella player version 6.5.4 (LL-HLS support)
- Fix: Annotation Tool signature issues (thanks to mliradelc)

## [3.5.11]
- Fix: add presenter stream before presentation to avoid missing audio in paella player

## [3.5.10]
- Fix: tile view always showed Annotation & Download buttons

## [3.5.9]
- Change: ws v7.4.6
- Change: hosted-git-info v2.8.9

## [3.5.8]
- Improvement: allow to copy and link objects in all possible views (repository "manage" function, repository actions dropdown and copying containers)

## [3.5.7]
- Fix: Prevent race condition when creating series via internal api
- Fix: action menus crossed the window border
- Fix: bad caching led to wrong ref ids (internal API)
- Fix/Improvement: (Live streaming) player now checks individual chunklists before loading, to prevent an error with multi-resolution streams
- Doc: added ROLE_SUDO to required roles in configuration doc

## [3.5.6]
- Fix: modals didn't work with config "Load event table synchronously"

## [3.5.5]
- Fix: error when creating new object
- Fix: remove ssl default version for curl

## [3.5.4]
- Fix: settings form was filled out with old values after changing title or description
- Fix: removed unnecessary signing of download urls
- Change/Improvement: ILIAS object's title and description are synchronized with Opencast series only when opening the settings (this saves requests and boosts performance). Additionally, when saving the settings, all objects linked to the same series are updated (this increases the loading time for saving the settings but prevents inconsistent data).

## [3.5.3]
- Feature: paella player version 6.5.2 (thanks to mliradelc)
- Improvement: add user to producers when opening opencast studio (thanks to rfcmaXi)
- Fix: fixed some lang vars (thanks to reiferschris)

## [3.5.2]
- Fix: joining a live event always showed a (wrong) status overlay first 
- Change: set default permission template when creating series with xoctSeriesAPI

## [3.5.1]
- Fix: small code change for InteractiveVideo plugin
- Fix: removed php 7.1 syntax

## [3.5.0]
- Improvement: Paella Player v6.5.0 (fixed some minor issues)
- Improvement: better performance by ommiting some unnecessary requests
- Improvement: allow upload of .m4v files
- Improvement: added available playback rates 1.75 and 2.0
- Improvement: check and filter not-transmitting streams for multi-live-streams
- Improvement: avoid showing 'interrupted' message for delayed live streams
- Improvement: prevent player from loading last used profile (=layout)
- Improvement/fix: new series - disable "choose existing series" if none available
- Feature: show column for unprotected link only if such link is present (and restricted to edit_videos permission)
- Feature: config for presigning urls (possible performance boost)
- Feature: config for synchronous loading of table (possible performance boost)
- Feature: additional option 'Login' for user mapping
- Feature: config for making "Presenter" field mandatory (thanks mliradelc!)
- Fix: internal player showed two streams but no quality selector on a single stream with multiple qualities
- Fix: omit error when emptying trash with old Opencast object via cron job
- Fix: show preview image in paella player
- Fix: fixed user filter in 'change owner' and 'grant access' views (was case-sensitive)
- Fix: fixed time format for reports table (plugin configuration)
- Fix: fixed Opencast Studio return link (thanks LukasKalbertodt!)

## [3.4.2]
- Fix: upload error (wrong acl format)

## [3.4.1]
- Fix: paella time-marks plugin broke live stream player

## [3.4.0]
- Fix/Improvement: Updated Paella Player to v6.4.3 (fixes broken player buttons)

## [3.3.4]
- Fix: properly show scheduling conflict messages
- Fix: Fatal error when opening workflow configuration
- Fix: Unpublish Workflow didn't work properly in some situations

## [3.3.3]
- Added config for 'Common IdP' (currently used for OpencastPageComponent's permission checks)

## [3.3.2]
- Refactored download publications for easier integration in InteractiveVideo plugin

## [3.3.1]
- Fix save object metadata with % character

## [3.3.0]
- Download event button has now the option "External download Source"
- Added Labels for FullHD and UltraHD
- Add support for Opencast Studio return link

## [3.2.0]
- Fix latest ILIAS 6
- Fix republish workflows with same name
- Add line break after each iframe
- Remove possible iframe border
- Fix Docker-ILIAS log
- Min. PHP 7.0

## [3.1.1]
- Follow curl redirects
- Fix core autoload conflict

## [3.1.0]
- Change: ILIAS 6 compatibility
- Change: dropped ILIAS 5.3 compatibility
- Fix: Fixed small caching bugs

## [3.0.0]
- Feature: allow multiple downloads
- Feature: new "Download Fallback" publication
- Feature: republish events with configurable workflows
- Feature: video upload via ingest nodes configurable
- Feature: configure tags for publications
- Feature: preview publication configurable
- Feature: signed link duration configurable for all link types
- Feature: signed links can be restricted to the current ip address
- Feature: "unprotected link" publication to show copyable link in event list
- Improvement: finally a configuration manual
- Improvement: introduced default values for publication configuration (a "standard" Opencast should work without any publications configured now)
- Improvement: refactored publications 
- Improvement: removed api publication (unused)
- Improvement: hide download-/annotation-related settings in series form if corresponding publications don't exist
- Fixed: config export bug
- Refactoring and adjustments for page component plugin

## [2.5.1]
- Bugfix: Avoid upload bug by using unique IDs to save files
- Bugfix: Fixed cleanup of old temp files

## [2.5.0]
- Feature: Opencast Studio button in series

## [2.4.2]
- Fixed race condition when creating series

## [2.4.1]
- Fixed Tile sorting
- Feature/Fix: introduced "Show in Form (checkbox active)" for workflow parameters

## [2.4.0]
- Dropped PHP 5.6 Support
- Feature: Live Streams
- Feature: Chat for Live Streams
- Improvement: Uploaded videos will now be saved as 'presentation' instead of 'presenter' (to trigger segmentation)
- Bugfix: Workflow Parameters not available for new series
- Bugfix: Fixed Typos
- Bugfix: fetching scheduling of events without scheduling led to crash
- Bugfix: wrong organizers when creating multiple series via internal API
- Bugfix: changed to list view and removed buttons when reloading event list
- Bugfix (skin): dropdown menus in tile view had broken hover effects
- Change: add organizer & contributer only when creating a new series 
- Code: Started refactoring the model, made changes for PageComponent plugin

## [2.3.2]
- Bugfix: invalid/wrong annotation links
- Paella Player v6.2.2

## [2.3.1]
- Bugfix: Tile View always showed annotation & download buttons

## [2.3.0]
- Support for ILIAS 5.4
- Dropped support for ILIAS 5.2
- Feature: Tile View
- Feature: Set field 'presenter/s' required for scheduled events

## [2.2.1]
- Bugfix: Config import not working

## [2.2.0]
- Feature: Configuration of Workflow-Parameters
- Bugfixing

## [2.1.1]
- Bugfix: Fixed major bug introduced with v2.1.0.

## [2.1.0]
- Feature: Plugin Configuration -> Reports
- Feature: Paella Player v6.1 with Streaming Support
- Feature: Publication "Segments" for Paella Player
- Improvement: GUI Streamlining
- Feature: Video Owner can change Metadata
- Bugfixing


## [1.0.0]
