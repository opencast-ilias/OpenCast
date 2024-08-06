<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Event\Request;

class UploadEventRequest
{
    public function __construct(protected UploadEventRequestPayload $payload)
    {
    }

    public function getPayload(): UploadEventRequestPayload
    {
        return $this->payload;
    }
}
