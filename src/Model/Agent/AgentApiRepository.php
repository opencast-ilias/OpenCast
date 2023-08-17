<?php

namespace srag\Plugins\Opencast\Model\Agent;

use srag\Plugins\Opencast\API\OpencastAPI;

class AgentApiRepository implements AgentRepository
{
    /**
     * @var AgentParser
     */
    private $agentParser;

    public function __construct(AgentParser $agentParser)
    {
        $this->agentParser = $agentParser;
    }

    public function findAll(): array
    {
        $data = OpencastAPI::getApi()->agentsApi->getAll();
        return $this->agentParser->parseApiResponse($data);
    }
}
