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

    /**
     * @param string $identifier
     * @param UpdateSeriesACLRequestPayload $payload
     */
    public function __construct(string $identifier, UpdateSeriesACLRequestPayload $payload)
    {
        $this->identifier = $identifier;
        $this->payload = $payload;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return UpdateSeriesACLRequestPayload
     */
    public function getPayload(): UpdateSeriesACLRequestPayload
    {
        return $this->payload;
    }

}