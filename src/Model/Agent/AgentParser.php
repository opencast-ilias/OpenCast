<?php

namespace srag\Plugins\Opencast\Model\Agent;

use DateTimeImmutable;
use Exception;
use stdClass;

class AgentParser
{
    /**
     * @param array $response
     * @return Agent[]
     * @throws Exception
     */
    public function parseApiResponse(array $response) : array
    {
        return array_map(function(stdClass $item) {
            return new Agent(
                $item->agent_id,
                $item->status,
                $item->inputs,
                new DateTimeImmutable($item->update),
                $item->url
            );
        }, $response);
    }
}