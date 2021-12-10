<?php

namespace srag\Plugins\Opencast\Model\Scheduling;

use xoctRequest;

class SchedulingApiRepository implements SchedulingRepository
{
    /**
     * @var SchedulingParser
     */
    private $scheduling_parser;

    /**
     * @param SchedulingParser $scheduling_parser
     */
    public function __construct(SchedulingParser $scheduling_parser)
    {
        $this->scheduling_parser = $scheduling_parser;
    }

    public function find(string $identifier) : Scheduling
    {
        $data = json_decode(xoctRequest::root()->events($identifier)->scheduling()->get());
        return $this->scheduling_parser->parseApiResponse($data);
    }
}