<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Metadata;

use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;

class MetadataFactory
{
    public const MD_TITLE_EVENT = 'EVENTS.EVENTS.DETAILS.CATALOG.EPISODE';
    public const MD_TITLE_SERIS = 'Opencast Series DublinCore';

    public function __construct(private readonly MDCatalogueFactory $catalogueFactory)
    {
    }

    public function event(): Metadata
    {
        return new Metadata(
            $this->catalogueFactory->event(),
            self::MD_TITLE_EVENT,
            Metadata::FLAVOR_DUBLINCORE_EPISODES
        );
    }

    public function series(): Metadata
    {
        return new Metadata(
            $this->catalogueFactory->series(),
            self::MD_TITLE_SERIS,
            Metadata::FLAVOR_DUBLINCORE_SERIES
        );
    }
}
