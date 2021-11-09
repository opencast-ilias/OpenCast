<?php

namespace srag\Plugins\Opencast\Model\Metadata;

use ILIAS\DI\Container;
use srag\Plugins\Opencast\Cache\Cache;
use srag\Plugins\Opencast\Model\API\Metadata\MetadataFactory;
use srag\Plugins\Opencast\Model\API\Metadata\MetadataRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\Series\MDFieldConfigSeriesRepository;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use srag\Plugins\Opencast\Model\Metadata\Helper\FormItemBuilder;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDPrefiller;

class MetadataDIC
{
    /**
     * @var Cache
     */
    private $cache;
    /**
     * @var Container
     */
    private $dic;

    /**
     * @var MDCatalogueFactory
     */
    private $MDCatalogueFactory;
    /**
     * @var MetadataFactory
     */
    private $metadataFactory;
    /**
     * @var MetadataRepository
     */
    private $metadataRepository;
    /**
     * @var MDParser
     */
    private $MDParser;
    /**
     * @var MDPrefiller
     */
    private $MDPrefiller;
    /**
     * @var FormItemBuilder
     */
    private $eventFormBuilder;
    /**
     * @var FormItemBuilder
     */
    private $seriesFormBuilder;
    /**
     * @var MDFieldConfigEventRepository
     */
    private $confRepositoryEvent;
    /**
     * @var MDFieldConfigSeriesRepository
     */
    private $confRepositorySeries;

    public function __construct(Cache $cache, Container $dic)
    {
        $this->cache = $cache;
        $this->dic = $dic;
    }


    public function catalogueFactory() : MDCatalogueFactory
    {
        if (is_null($this->MDCatalogueFactory)) {
            $this->MDCatalogueFactory = new MDCatalogueFactory();
        }
        return $this->MDCatalogueFactory;
    }

    public function metadataFactory() : MetadataFactory
    {
        if (is_null($this->metadataFactory)) {
            $this->metadataFactory = new MetadataFactory($this->catalogueFactory());
        }
        return $this->metadataFactory;
    }

    public function repository() : MetadataRepository
    {
        if (is_null($this->metadataRepository)) {
            $this->metadataRepository = new MetadataRepository($this->cache, $this->parser());
        }
        return $this->metadataRepository;
    }

    public function parser() : MDParser
    {
        if (is_null($this->MDParser)) {
            $this->MDParser = new MDParser($this->catalogueFactory(), $this->metadataFactory());
        }
        return $this->MDParser;
    }

    public function prefiller() : MDPrefiller
    {
        if (is_null($this->MDPrefiller)) {
            $this->MDPrefiller = new MDPrefiller();
        }
        return $this->MDPrefiller;
    }

    public function confRepositoryEvent() : MDFieldConfigEventRepository
    {
        if (is_null($this->confRepositoryEvent)) {
            $this->confRepositoryEvent = new MDFieldConfigEventRepository();
        }
        return $this->confRepositoryEvent;
    }

    public function confRepositorySeries() : MDFieldConfigSeriesRepository
    {
        if (is_null($this->confRepositorySeries)) {
            $this->confRepositorySeries = new MDFieldConfigSeriesRepository();
        }
        return $this->confRepositorySeries;
    }

    public function formBuilderEvent() : FormItemBuilder
    {
        if (is_null($this->eventFormBuilder)) {
            $this->eventFormBuilder = new FormItemBuilder($this->catalogueFactory()->event(),
                $this->confRepositoryEvent(),
                $this->prefiller(),
                $this->dic->ui()->factory());
        }
        return $this->eventFormBuilder;
    }

    public function formBuilderSeries() : FormItemBuilder
    {
        if (is_null($this->seriesFormBuilder)) {
            $this->seriesFormBuilder = new FormItemBuilder($this->catalogueFactory()->series(),
                $this->confRepositorySeries(),
                $this->prefiller(),
                $this->dic->ui()->factory());
        }
        return $this->seriesFormBuilder;
    }
}