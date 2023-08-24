<?php

namespace srag\Plugins\Opencast\Model\Series\Request;

class UpdateSeriesACLRequest
{
    /**
     * @var string
     */
    private $identifier;
    /**
     * @var UpdateSeriesACLRequestPayload
     */
    private $payload;

    public function __construct(string $identifier, UpdateSeriesACLRequestPayload $payload)
    {
        $this->identifier = $identifier;
        $this->payload = $payload;
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
