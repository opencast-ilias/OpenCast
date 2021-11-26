<?php

namespace srag\Plugins\Opencast\Model\Metadata;

use ILIAS\DI\Container;
use srag\Plugins\Opencast\Cache\Cache;
use srag\Plugins\Opencast\Model\API\Metadata\MetadataFactory;
use srag\Plugins\Opencast\Model\API\Metadata\MetadataAPIRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\Series\MDFieldConfigSeriesRepository;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use srag\Plugins\Opencast\Model\Metadata\Helper\FormBuilder;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDFormItemBuilder;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDPrefiller;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameterRepository;
use srag\Plugins\Opencast\Util\Upload\UploadStorageService;
use xoctFileUploadHandler;

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
     * @var MetadataAPIRepository
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
     * @var MDFormItemBuilder
     */
    private $eventFormBuilder;
    /**
     * @var MDFormItemBuilder
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
    /**
     * @var FormBuilder
     */
    private $formBuilder;

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

    public function apiRepository() : MetadataAPIRepository
    {
        if (is_null($this->metadataRepository)) {
            $this->metadataRepository = new MetadataAPIRepository($this->cache, $this->parser());
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

    public function formBuilderEvent() : FormBuilder
    {
        if (is_null($this->formBuilder)) {
            // TODO: is this the correct place to init uploadstorageservice and xoctEventFormGUI?
            $upload_storage_service = new UploadStorageService($this->dic->filesystem()->temp(), $this->dic->upload());
            $this->formBuilder = new FormBuilder(
                $this->dic->ui()->factory(),
                $this->dic->refinery(),
                $this->formItemBuilder(),
                new SeriesWorkflowParameterRepository($this->dic->ui()->factory()),
                new xoctFileUploadHandler($upload_storage_service),
                $upload_storage_service
            );
        }
        return $this->formBuilder;
    }

    public function formItemBuilder() : MDFormItemBuilder
    {
        if (is_null($this->eventFormBuilder)) {
            $this->eventFormBuilder = new MDFormItemBuilder($this->catalogueFactory()->event(),
                $this->confRepositoryEvent(),
                $this->prefiller(),
                $this->dic->ui()->factory(),
                $this->dic->refinery());
        }
        return $this->eventFormBuilder;
    }

    public function formBuilderSeries() : MDFormItemBuilder
    {
        if (is_null($this->seriesFormBuilder)) {
            $this->seriesFormBuilder = new MDFormItemBuilder($this->catalogueFactory()->series(),
                $this->confRepositorySeries(),
                $this->prefiller(),
                $this->dic->ui()->factory(),
                $this->dic->refinery());
        }
        return $this->seriesFormBuilder;
    }
}