<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Series\Request;

class UpdateSeriesACLRequest
{
    public function __construct(private readonly string $identifier, private readonly UpdateSeriesACLRequestPayload $payload)
    {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getPayload(): UpdateSeriesACLRequestPayload
    {
        return $this->payload;
    }
}
