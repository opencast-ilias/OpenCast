# Scheduling

Scheduling data is part of a scheduled event.
See https://docs.opencast.org/develop/developer/#api/events-api/#scheduling-information
It consists of the following properties:

- agent-id: identifier of the capture agent responsible for the recording (see [Agent](../Agent/README.md))
- start: start date & time of the recording
- end: optional end date & time of the recording (can be replaced by duration)
- duration: optional duration of the recording (can be replaced by end)
- RRule: used to create multiple scheduled events,
  see https://docs.opencast.org/develop/developer/#api/types/#recurrence-rule
- inputs: I have no idea, it's always "default". Probably used if the capture device has multiple inputs.

Note that after a scheduled event is recorded, parts of the scheduling data merge into the events metadata:

- "agent-id" becomes "location"
- "start" becomes "startDate"
- "duration"/"end" becomes "duration"
