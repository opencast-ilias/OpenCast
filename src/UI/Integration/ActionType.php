<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 * @internal
 */
enum ActionType: string
{
    case INTERNAL_LINK = 'link';
    case EXTERNAL_LINK = 'ext_link';
    case ASYNC_MODAL = 'async_modal';
}
