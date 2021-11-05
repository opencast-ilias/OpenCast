<?php

namespace srag\Plugins\Opencast\Model\API\Metadata;

use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use srag\Plugins\Opencast\Model\Metadata\MetadataDIC;

class MetadataFactory
{
    const MD_TITLE_EVENT = 'EVENTS.EVENTS.DETAILS.CATALOG.EPISODE';
    const MD_TITLE_SERIS = 'Opencast Series DublinCore';

    /**
     * @var MDCatalogueFactory
     */
    private $md_catalogue_factory;

    public function __construct(MetadataDIC $metadataDIC)
    {
        $this->md_catalogue_factory = $metadataDIC->catalogueFactory();
    }

    public function eventMetadata() : Metadata
    {
        return new Metadata(
            $this->md_catalogue_factory->event(),
            self::MD_TITLE_EVENT,
        Metadata::FLAVOR_DUBLINCORE_EPISODES
        );
    }

    public function seriesMetadata() : Metadata
    {
        return new Metadata(
            $this->md_catalogue_factory->series(),
            self::MD_TITLE_SERIS,
        Metadata::FLAVOR_DUBLINCORE_SERIES
        );
    }

}