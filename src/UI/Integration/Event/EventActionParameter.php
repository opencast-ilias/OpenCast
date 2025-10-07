<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration\Event;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
enum EventActionParameter: string
{
    case EVENT_ID = 'eid';
    case EVENT_OBJECT = 'object';

}
