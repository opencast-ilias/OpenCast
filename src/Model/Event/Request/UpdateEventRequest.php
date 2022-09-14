<?php

namespace srag\Plugins\Opencast\Model\Event\Request;

class UpdateEventRequest
{
    /**
     * @var string
     */
    protected $identifier;
    /**
     * @var UpdateEventRequestPayload
     */
    protected $payload;

    public function __construct(
        string $identifier,
        UpdateEventRequestPayload $payload
    )
    {
        $this->identifier = $identifier;
        $this->payload = $payload;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getPayload(): UpdateEventRequestPayload
    {
        return $this->payload;
    }
}
