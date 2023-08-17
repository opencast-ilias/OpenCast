<?php

namespace srag\Plugins\Opencast\Model\Publication;

use srag\Plugins\Opencast\Model\Cache\Cache;
use srag\Plugins\Opencast\API\OpencastAPI;
use srag\Plugins\Opencast\API\API;

class PublicationAPIRepository implements PublicationRepository
{
    /**
     * @var Cache
     */
    private $cache;
    /**
     * @var API
     */
    protected $api;

    public function __construct(Cache $cache)
    {
        global $opencastContainer;
        $this->api = $opencastContainer[API::class];
        $this->cache = $cache;
    }

    public function find(string $identifier): array
    {
        return $this->cache->get('event-pubs-' . $identifier)
            ?? $this->fetch($identifier);
    }

    public function fetch(string $identifier): array
    {
        $data = $this->api->routes()->eventsApi->getPublications($identifier);
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
