<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Event\Request;

class UpdateEventRequest
{
    public function __construct(protected string $identifier, protected UpdateEventRequestPayload $payload)
    {
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
