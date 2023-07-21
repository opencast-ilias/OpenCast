<?php

namespace srag\Plugins\Opencast\Model\Agent;

use Exception;
use xoctOpencastApi;

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
        $data = xoctOpencastApi::getApi()->agentsApi->getAll();
        return $this->agentParser->parseApiResponse($data);
    }
}
