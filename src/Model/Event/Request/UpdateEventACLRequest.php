<?php

namespace srag\Plugins\Opencast\Model\Event\Request;

class UpdateEventACLRequest
{
    /**
     * @var string
     */
    private $identifier;
    /**
     * @var UpdateEventACLRequestPayload
     */
    private $payload;

    /**
     * @param string $identifier
     * @param UpdateEventACLRequestPayload $payload
     */
    public function __construct(string $identifier, UpdateEventACLRequestPayload $payload)
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
     * @return UpdateEventACLRequestPayload
     */
    public function getPayload(): UpdateEventACLRequestPayload
    {
        return $this->payload;
    }



}