# Change Log

## [3.5.0]
- Improvement: better performance by ommiting some unnecessary requests
- Improvement: allow upload of .m4v files
- Improvement: added available playback rates 1.75 and 2.0
- Improvement: check and filter not-transmitting streams for multi-live-streams
- Improvement: avoid showing 'interrupted' message for delayed live streams
- Feature: show column for unprotected link only if such link is present (and restricted to edit_videos permission)
- Feature: config for presigning urls (possible performance boost)
- Feature: config for synchronous loading of table (possible performance boost)
- Feature: additional option 'Login' for user mapping
- Fix: internal player showed two streams but no quality selector on a single stream with multiple qualities
- Fix: omit error when emptying trash with old Opencast object via cron job
- Fix: show preview image in paella player

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
- Change: ILIAS 6 compatability
- Change: dropped ILIAS 5.3 compatability
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
