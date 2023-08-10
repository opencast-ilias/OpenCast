<?php

namespace srag\Plugins\Opencast\Model\Publication;

use srag\Plugins\Opencast\Model\Cache\Cache;
use xoctRequest;

class PublicationAPIRepository implements PublicationRepository
{
    /**
     * @var Cache
     */
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function find(string $identifier): array
    {
        return $this->cache->get('event-pubs-' . $identifier)
            ?? $this->fetch($identifier);
    }

    public function fetch(string $identifier): array
    {
        $data = json_decode(
            xoctRequest::root()->events($identifier)->publications()->get(),
            null,
            512,
            JSON_THROW_ON_ERROR
        );
        $publications = [];
        foreach ($data as $d) {
            $p = new Publication();
            $p->loadFromStdClass($d);
            $publications[] = $p;
        }
        $this->cache->set('event-pubs-' . $identifier, $publications);
        return $publications;
    }
}
