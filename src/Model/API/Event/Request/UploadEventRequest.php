<?php

namespace srag\Plugins\Opencast\Model\API\Event;

class UploadEventRequest
{
    /**
     * @var UploadEventRequestPayload
     */
    protected $payload;

    public function __construct(UploadEventRequestPayload $payload)
    {
        $this->payload = $payload;
    }

    public function getPayload(): UploadEventRequestPayload
    {
        return $this->payload;
    }
}