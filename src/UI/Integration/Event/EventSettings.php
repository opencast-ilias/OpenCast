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
    case LABELS_AS_GLYPHS = 'glyphs_instead_of_labels';
    case PRESENTED_METADATA = 'presented_metadata';

}
