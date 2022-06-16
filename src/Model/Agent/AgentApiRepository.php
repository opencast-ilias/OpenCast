<?php

namespace srag\Plugins\Opencast\Model\Agent;

use Exception;
use xoctRequest;

class AgentApiRepository implements AgentRepository
{
    /**
     * @var AgentParser
     */
    private $agentParser;

    /**
     * @param AgentParser $agentParser
     */
    public function __construct(AgentParser $agentParser)
    {
        $this->agentParser = $agentParser;
    }

    public function findAll(): array
    {
        $data = json_decode(xoctRequest::root()->agents()->get());
        return $this->agentParser->parseApiResponse($data);
    }
}
