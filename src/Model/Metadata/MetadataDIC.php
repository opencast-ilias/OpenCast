<?php

namespace srag\Plugins\Opencast\Model\Metadata;

use srag\Plugins\Opencast\Cache\Cache;
use srag\Plugins\Opencast\Model\API\Metadata\MetadataFactory;
use srag\Plugins\Opencast\Model\API\Metadata\MetadataRepository;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;

class MetadataDIC
{
    /**
     * @var Cache
     */
    private $cache;
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

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
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
            $this->metadataFactory = new MetadataFactory($this);
        }
        return $this->metadataFactory;
    }

    public function metadataRepository() : MetadataRepository
    {
        if (is_null($this->metadataRepository)) {
            $this->metadataRepository = new MetadataRepository($this->cache, $this);
        }
        return $this->metadataRepository;
    }

    public function metadataParser() : MDParser
    {
        if (is_null($this->MDParser)) {
            $this->MDParser = new MDParser($this);
        }
        return $this->MDParser;
    }
}