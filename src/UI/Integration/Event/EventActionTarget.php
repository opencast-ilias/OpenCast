<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration\Event;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
enum EventActionTarget: string
{
    case PLAY = 'play';
    case DOWNLOAD = 'download';
    case ANNOTATE = 'annotate';
    case CUT = 'cut';
    case START_WORKFLOW = 'start_workflow';
    case SET_OFFLINE = 'set_offline';
    case SET_ONLINE = 'set_online';
    case DELETE = 'delete';
    case EDIT_METADATA = 'edit_metadata';
    case REPORT_QUALITY_ISSUE = 'report_quality_issue';
    case REPORT_DATE_MODIFICATION = 'report_date_modification';
    case GRANT_ACCESS = 'grant_access';
    case EDIT_OWNER = 'edit_owner';
    case REPUBLISH = 'republish';

}
