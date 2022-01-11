<?php

namespace srag\Plugins\Opencast\Model\Series\Request;

class UpdateSeriesRequest
{
    /**
     * @var string
     */
    protected $identifier;
    /**
     * @var UpdateSeriesRequestPayload
     */
    protected $payload;

    public function __construct(string $identifier, UpdateSeriesRequestPayload $payload)
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
     * @return UpdateSeriesRequestPayload
     */
    public function getPayload(): UpdateSeriesRequestPayload
    {
        return $this->payload;
    }

}