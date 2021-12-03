<?php

namespace srag\Plugins\Opencast\Model\Metadata;

use Pimple\Container;
use srag\Plugins\Opencast\Model\API\Metadata\MetadataAPIRepository;
use srag\Plugins\Opencast\Model\API\Metadata\MetadataFactory;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\Series\MDFieldConfigSeriesRepository;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDPrefiller;

class MetadataService
{

    /**
     * @var Container
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }


    public function catalogueFactory() : MDCatalogueFactory
    {
        return $this->container['md_catalogue_factory'];
    }

    public function metadataFactory() : MetadataFactory
    {
        return $this->container['md_factory'];
    }

    public function apiRepository() : MetadataAPIRepository
    {
        return $this->container['md_repository'];
    }

    public function parser() : MDParser
    {
        return $this->container['md_parser'];
    }

    public function prefiller() : MDPrefiller
    {
        return $this->container['md_prefiller'];
    }

    public function confRepositoryEvent() : MDFieldConfigEventRepository
    {
        return $this->container['md_conf_repository_event'];
    }

    public function confRepositorySeries() : MDFieldConfigSeriesRepository
    {
        return $this->container['md_conf_repository_event'];
    }
}