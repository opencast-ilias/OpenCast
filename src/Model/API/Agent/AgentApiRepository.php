<?php

namespace srag\Plugins\Opencast\Model\API\Agent;

use Exception;
use xoctRequest;

class AgentApiRepository
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

    /**
     * @return Agent[]
     * @throws Exception
     */
    public function findAll() : array
    {
        $data = json_decode(xoctRequest::root()->agents()->get());
        return $this->agentParser->parseApiResponse($data);
    }
}