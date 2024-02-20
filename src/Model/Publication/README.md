# Publications

After an event is recorded or uploaded, Opencast executes a workflow and creates
publications of an event. Therefore,
only events with state STATE_SUCCEEDED (meaning the
workflow has successfully finished) have publications.

Publications contain a lot of data required by the plugin: urls to thumbnails,
videos in different formats, etc. The
difficulty lies in how to retrieve the correct information. Depending on the
configuration of Opencast, the
identification
of publications might differ. That's why the plugin has a configuration for the
different publication usages.

For a list of the different usages and how to configure them,
see [here](../../../doc/CONFIGURATION.md#Publications).

## Architecture

### Config

The PublicationUsage class is an active record representing the usage
configuration. It is managed by a
PublicationUsageRepository.

The PublicationUsageDefault class statically returns default values for the most
important usages.

### Publication

The actual Publication object. Can be fetched via the PublicationRepository -
although this is mostly not necessary,
since the EventRepository can fetch the publications of an event in the same
request as fetching the rest of the event,
thus saving time and resources.

Attachment and Media are data objects contained in a publication.

### PublicationSelector

The PublicationSelector is responsible for filtering an event's publications and
selecting the correct properties,
according to the plugin configuration (PublicationUsages). So there are public
methods like getPlayerLink, which selects
the right publication and retrieves the correct field inside the publication
which must be the player link, according
to the PublicationUsage configuration.

The event object has a PublicationSelector as an instance variable, instead of a
set of Publications. Not sure how much
sense this makes from an architectural perspective, but it works for the time
being.
