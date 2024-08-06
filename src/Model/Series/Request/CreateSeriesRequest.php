<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Series\Request;

class CreateSeriesRequest
{
    public function __construct(private readonly CreateSeriesRequestPayload $payload)
    {
    }

    public function getPayload(): CreateSeriesRequestPayload
    {
        return $this->payload;
    }
}
