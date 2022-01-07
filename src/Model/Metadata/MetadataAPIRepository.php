<?php

namespace srag\Plugins\Opencast\Model\Metadata;

use srag\Plugins\Opencast\Cache\Cache;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use xoctException;
use xoctRequest;

class MetadataAPIRepository implements MetadataRepository
{
    const CACHE_PREFIX = 'event-md-';

    /**
     * @var Cache
     */
    private $cache;
    /**
     * @var MDParser
     */
    private $parser;

    public function __construct(Cache $cache, MDParser $parser)
    {
        $this->cache = $cache;
        $this->parser = $parser;
    }

    public function findEventMD(string $identifier) : Metadata
    {
        return $this->cache->get('event-md-' . $identifier)
            ?? $this->fetchEventMD($identifier);
    }

    public function fetchEventMD(string $identifier) : Metadata
    {
        $data = json_decode(xoctRequest::root()->events($identifier)->metadata()->get()) ?? [];
        $metadata = $this->parser->parseAPIResponseEvent($data);
        $this->cache->set('event-md-' . $identifier, $metadata);
        return $metadata;
    }

    public function findSeriesMD(string $identifier) : Metadata
    {
        return $this->cache->get('series-md-' . $identifier)
            ?? $this->fetchSeriesMD($identifier);
    }

    public function fetchSeriesMD(string $identifier) : Metadata
    {
        $data = json_decode(xoctRequest::root()->series($identifier)->metadata()->get()) ?? [];
        $metadata = $this->parser->parseAPIResponseSeries($data);
        $this->cache->set('series-md-' . $identifier, $metadata);
        return $metadata;
    }
}