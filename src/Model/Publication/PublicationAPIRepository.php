<?php

namespace srag\Plugins\Opencast\Model\Publication;

use srag\Plugins\Opencast\API\API;
use srag\Plugins\Opencast\Model\Cache\Container\Request;
use srag\Plugins\Opencast\Model\Cache\Services;
use srag\Plugins\Opencast\Model\Cache\Container\Container;

class PublicationAPIRepository implements PublicationRepository, Request
{
    /**
     * @var Container
     */
    private $cache;
    /**
     * @var API
     */
    protected $api;

    public function __construct()
    {
        global $opencastContainer;
        $this->api = $opencastContainer[API::class];
        $this->cache = $opencastContainer[Services::class]->get($this);
    }

    public function getContainerKey(): string
    {
        return 'publications';
    }

    public function find(string $identifier): array
    {
        return $this->fetch($identifier);
    }

    public function fetch(string $identifier): array
    {
        if ($this->cache->has($identifier)) {
            $data = $this->cache->get($identifier);
        } else {
            $data = $this->api->routes()->eventsApi->getPublications($identifier);
            $this->cache->set($identifier, $data);
        }
        $publications = [];
        foreach ($data as $d) {
            $p = new Publication();
            $p->loadFromStdClass($d);
            $publications[] = $p;
        }

        return $publications;
    }
}
