<?php

namespace srag\Plugins\Opencast\Model\Series\Request;

class CreateSeriesRequest
{
    /**
     * @var CreateSeriesRequestPayload
     */
    private $payload;

    /**
     * @param CreateSeriesRequestPayload $payload
     */
    public function __construct(CreateSeriesRequestPayload $payload)
    {
        $this->payload = $payload;
    }

    /**
     * @return CreateSeriesRequestPayload
     */
    public function getPayload(): CreateSeriesRequestPayload
    {
        return $this->payload;
    }
}
