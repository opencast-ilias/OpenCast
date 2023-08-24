<?php

namespace srag\Plugins\Opencast\Model\Series\Request;

class UpdateSeriesMetadataRequest
{
    /**
     * @var string
     */
    protected $identifier;
    /**
     * @var UpdateSeriesMetadataRequestPayload
     */
    protected $payload;

    public function __construct(string $identifier, UpdateSeriesMetadataRequestPayload $payload)
    {
        $this->payload = $payload;
        $this->identifier = $identifier;
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
