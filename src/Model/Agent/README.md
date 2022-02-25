# Agent

Opencast uses the concept of capture agents to schedule and automatically record events. A capture agent is a piece of 
software, which can be installed on devices responsible for recording events. The scheduling/recording workflow:
- the capture agent will register with the opencast installation
- events can now be scheduled via Opencast for the registered capture agent
- the capture agent regularly fetches all its scheduled events
- the capture agent automatically records events for the given time range
- the capture agent uploads recorded events to Opencast

This component models the API repository and objects to fetch the configured agents from Opencast. These agents are then 
used to schedule events via the Plugin.

_Note that the agent-id becomes the metadata field 'location' after an event is recorded._

Most common Capture Agent: https://github.com/opencast/pyCA