<?php

namespace srag\Plugins\Opencast\Model\Agent;

use srag\Plugins\Opencast\API\API;
use srag\Plugins\Opencast\Model\Cache\Container\Request;
use srag\Plugins\Opencast\Model\Cache\Services;
use srag\Plugins\Opencast\Model\Cache\Container\Container;

class AgentApiRepository implements AgentRepository, Request
{
    /**
     * @var AgentParser
     */
    private $agentParser;
    /**
     * @var API
     */
    private $api;
    /**
     * @var Container
     */
    private $cache;

    public function __construct(AgentParser $agentParser)
    {
        global $opencastContainer;
        $this->agentParser = $agentParser;
        $this->api = $opencastContainer[API::class];
        $this->cache = $opencastContainer[Services::class]->get($this);
    }

    public function getContainerKey(): string
    {
        return 'agent';
    }

    public function findAll(): array
    {
        if ($this->cache->has('all')) {
            $data = $this->cache->get('all');
        } else {
            $data = $this->api->routes()->agentsApi->getAll();
            $this->cache->set('all', $data);
        }

        return $this->agentParser->parseApiResponse($data);
    }
}
