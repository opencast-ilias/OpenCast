<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Event\Request;

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
