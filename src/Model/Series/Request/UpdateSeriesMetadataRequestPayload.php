<?php

namespace srag\Plugins\Opencast\Model\Series\Request;

use JsonSerializable;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Metadata\MetadataField;

class UpdateSeriesMetadataRequestPayload implements JsonSerializable
{
    /**
     * @var Metadata
     */
    protected $metadata;

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
        // for some reason, label etc. are not allowed here (unlike for events)
        return ['metadata' => json_encode(array_map(function (MetadataField $field) {
            return $field->jsonSerialize();
        }, $this->metadata->getFields()))];
    }
}