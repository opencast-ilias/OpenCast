<?php

namespace srag\Plugins\Opencast\Model\Series\Request;

use JsonSerializable;
use srag\Plugins\Opencast\Model\Metadata\Metadata;

class UpdateSeriesRequestPayload implements JsonSerializable
{
    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * @param Metadata $metadata
     */
    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * @return Metadata
     */
    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }


    public function jsonSerialize()
    {
        return [
            'metadata' => json_encode([$this->metadata->jsonSerialize()]),
        ];
    }
}