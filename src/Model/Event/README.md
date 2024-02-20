# Event

## Definition

The most central and complex object. An event is basically a (lecture)
recording. Its structure depends on the current
state of the event; e.g. a "Scheduled" does not yet have any video material or
publications, but it does have a
"Scheduling" data object.

## Structure

### Metadata

General Information about the Event. All events have a metadata set, independent
of their current state.
See also [Metadata README](../Metadata/README.md).

### Publications

After an event is recorded or uploaded, Opencast executes a workflow and creates
publications of an event
(see [Publications README](../Publication/README.md)). Therefore, only events
with state STATE_SUCCEEDED (meaning the
workflow has successfully finished) have publications.

### ACL

Access Control Lists. Always present, regardless of the state.
See https://docs.opencast.org/develop/developer/#api/types/#access-control-lists

### Scheduling

Only available for scheduled events.
See [Scheduling README](../Scheduling/README.md).

### Workflows

Workflow Instances of an event. These can either be planned workflows (for
scheduled events) or running workflows (for
events that are currently being processed).

_These are currently not used in the plugin. They might become necessary though,
if the workflow parameters of sheduled
events should be editable._

