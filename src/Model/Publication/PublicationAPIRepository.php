<?php

namespace srag\Plugins\Opencast\Model\Publication;

use srag\Plugins\Opencast\Cache\Cache;
use xoctException;
use xoctPublication;
use xoctRequest;

class PublicationAPIRepository
{


    /**
     * @var Cache
     */
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param string $identifier
     * @return xoctPublication[]
     */
    public function find(string $identifier) : array
    {
        return $this->cache->get('event-pubs-' . $identifier)
            ?? $this->fetch($identifier);
    }

    /**
     * @param string $identifier
     * @return xoctPublication[]
     * @throws xoctException
     */
    public function fetch(string $identifier): array
    {
        $data = json_decode(xoctRequest::root()->events($identifier)->publications()->get());
        $publications = [];
        foreach ($data as $d) {
            $p = new xoctPublication();
            $p->loadFromStdClass($d);
            $publications[] = $p;
        }
        $this->cache->set('event-pubs-' . $identifier, $publications);
        return $publications;
    }
}