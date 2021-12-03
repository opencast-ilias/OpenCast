<?php

namespace srag\Plugins\Opencast\Model\Metadata;

use srag\Plugins\Opencast\Cache\Cache;
use srag\Plugins\Opencast\Model\Metadata\Helper\MDParser;
use xoctException;
use xoctRequest;

class MetadataAPIRepository
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

    /**
     * @throws xoctException
     */
    public function find(string $identifier) : Metadata
    {
        return $this->cache->get('event-md-' . $identifier)
            ?? $this->fetch($identifier);
    }

    /**
     * @param string $identifier
     * @return Metadata
     * @throws xoctException
     */
    public function fetch(string $identifier) : Metadata
    {
        $data = json_decode(xoctRequest::root()->events($identifier)->metadata()->get()) ?? [];
        $metadata = $this->parser->parseAPIResponseEvent($data);
        $this->cache->set('event-md-' . $identifier, $metadata);
        return $metadata;
    }
}