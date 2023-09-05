<?php

namespace srag\Plugins\Opencast\Model\Agent;

use DateTimeImmutable;
use Exception;
use stdClass;

class AgentParser
{
    /**
     * @return Agent[]
     * @throws Exception
     */
    public function parseApiResponse(array $response): array
    {
        return array_map(function (stdClass $item): \srag\Plugins\Opencast\Model\Agent\Agent {
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
