<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI\Integration\Series;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
enum SeriesActionParameter: string
{
    case SERIES_ID = 'sid';
    case SORT = 'sort';
    case PAGE = 'page';
    case FILTER = 'filter';
    case PAGE_SIZE = 'page_size';
}
