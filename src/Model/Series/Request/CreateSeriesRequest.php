<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Series\Request;

class CreateSeriesRequest
{
    /**
     * @var CreateSeriesRequestPayload
     */
    private $payload;

    public function __construct(CreateSeriesRequestPayload $payload)
    {
        $this->payload = $payload;
    }

    public function getPayload(): CreateSeriesRequestPayload
    {
        return $this->payload;
    }
}
