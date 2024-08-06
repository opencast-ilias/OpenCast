<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Series\Request;

class UpdateSeriesMetadataRequest
{
    public function __construct(protected string $identifier, protected UpdateSeriesMetadataRequestPayload $payload)
    {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getPayload(): UpdateSeriesMetadataRequestPayload
    {
        return $this->payload;
    }
}
