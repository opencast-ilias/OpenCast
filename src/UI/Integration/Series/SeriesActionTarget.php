<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration\Series;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
enum SeriesActionTarget: string
{
    case SET_ITEMS_PER_PAGE = 'setItemsPerPage';
    case SORT = 'sort';
    case PAGE = 'page';
    case FILTER = 'filter';

}
