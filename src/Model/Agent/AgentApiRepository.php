<?php

namespace srag\Plugins\Opencast\Model\Agent;

use srag\Plugins\Opencast\API\API;

class AgentApiRepository implements AgentRepository
{
    /**
     * @var AgentParser
     */
    private $agentParser;
    /**
     * @var API
     */
    private $api;

    public function __construct(AgentParser $agentParser)
    {
        global $opencastContainer;
        $this->agentParser = $agentParser;
        $this->api = $opencastContainer[API::class];
    }

    public function findAll(): array
    {
        $data = $this->api->routes()->agentsApi->getAll();
        return $this->agentParser->parseApiResponse($data);
    }
}
