<?php

namespace srag\Plugins\Opencast\Model\Event\Request;

class ScheduleEventRequest
{
    /**
     * @var ScheduleEventRequestPayload
     */
    protected $payload;

    public function __construct(ScheduleEventRequestPayload $payload)
    {
        $this->payload = $payload;
    }

    public function getPayload(): ScheduleEventRequestPayload
    {
        return $this->payload;
    }
}