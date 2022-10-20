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

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return UpdateSeriesMetadataRequestPayload
     */
    public function getPayload(): UpdateSeriesMetadataRequestPayload
    {
        return $this->payload;
    }
}
