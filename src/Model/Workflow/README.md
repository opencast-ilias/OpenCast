# Workflow

A workflow is a set of workflow operations which are executed on a media package in Opencast.
In the plugin, workflows are used in two places:

## Event Creation

When uploading or scheduling a new event, the plugin will send information about what workflow should be executed after
the upload/recording, together with flags for that workflow (workflow parameters).

The ID of the workflow to be executed is defined in the plugin configuration (
see [here](../../../doc/CONFIGURATION.md#Processing-Workflow-ID)).

The workflow parameters can be defined in the plugin configuration,
too: [here](../../../doc/CONFIGURATION.md#Workflow-Parameters).

The workflow ID + the workflow parameters are then sent as the 'Processing' variable to the Opencast API.
See https://docs.opencast.org/develop/developer/#api/events-api/#post-apievents

The code for this stuff is in the directory [Workflow Parameter](../WorkflowParameter).

## Start Workflow

You can define additional workflows in the plugin configuration: [Workflows](../../../doc/CONFIGURATION.md#Workflows).
These workflows
can be executed for existing events, via the Actions menu in the event table.

The model for this stuff is in the current directory "Workflow".
