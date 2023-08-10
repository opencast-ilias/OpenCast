<?php

namespace srag\Plugins\Opencast\Model\Agent;

use xoctRequest;

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
        $data = json_decode(xoctRequest::root()->agents()->get(), null, 512, JSON_THROW_ON_ERROR);
        return $this->agentParser->parseApiResponse($data);
    }
}
