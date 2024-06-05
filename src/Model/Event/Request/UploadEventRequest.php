<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Event\Request;

class UploadEventRequest
{
    protected UploadEventRequestPayload $payload;

    public function __construct(UploadEventRequestPayload $payload)
    {
        $this->payload = $payload;
    }

    public function getPayload(): UploadEventRequestPayload
    {
        return $this->payload;
    }
}
