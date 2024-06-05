<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Event\Request;

class ScheduleEventRequest
{
    protected ScheduleEventRequestPayload $payload;

    public function __construct(ScheduleEventRequestPayload $payload)
    {
        $this->payload = $payload;
    }

    public function getPayload(): ScheduleEventRequestPayload
    {
        return $this->payload;
    }
}
