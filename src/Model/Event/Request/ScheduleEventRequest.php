<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Event\Request;

class ScheduleEventRequest
{
    public function __construct(protected ScheduleEventRequestPayload $payload)
    {
    }

    public function getPayload(): ScheduleEventRequestPayload
    {
        return $this->payload;
    }
}
