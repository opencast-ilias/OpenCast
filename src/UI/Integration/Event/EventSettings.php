<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration\Event;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
enum EventSettings: string
{
    case SHOW_OWNER = 'show_owner';
    case PLAYER_AS_MODAL = 'player_as_modal';
    case SHOW_BEST_ACTION = 'best_action';
    case PRESENTED_METADATA = 'presented_metadata';

    case DESCRIPTION_MAX_LENGTH = 'description_max_length';
    case STATUS_MAX_LENGTH = 'status_max_length';
    case EDIT_ALL_METADATA = 'edit_all_metadata';
    case USE_ANNOTATIONS = 'use_annotations';

    case START_X_MINUTES_BEFORE_LIVE = 'minutes_before_live';

}
