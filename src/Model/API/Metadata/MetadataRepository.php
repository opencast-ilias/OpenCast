<?php

namespace srag\Plugins\Opencast\Model\API\Metadata;

use Metadata;
use srag\Plugins\Opencast\Cache\Cache;
use xoctException;
use xoctRequest;

class MetadataRepository
{


    /**
     * @var Cache
     */
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

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
        foreach ($data as $d) {
            if ($d->flavor == Metadata::FLAVOR_DUBLINCORE_EPISODES) {
                $metadata = new Metadata();
                $metadata->loadFromArray((array)$d);
                break;
            }
        }
        if (!isset($metadata)) {
            throw new xoctException(xoctException::INTERNAL_ERROR,
                'Metadata for event could not be loaded: ' . $identifier);
        }
        $this->cache->set('event-md-' . $identifier, $metadata);
        return $metadata;
    }
}