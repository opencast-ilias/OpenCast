<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Series\Request;

use JsonSerializable;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Metadata\MetadataField;

class UpdateSeriesMetadataRequestPayload implements JsonSerializable
{
    use SanitizeSeriesMetadata;

    protected Metadata $metadata;

    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    /**
     * @return array{metadata: string}
     */
    public function jsonSerialize()
    {
        $this->saniziteMetadataFields($this->metadata->getFields()); // to prevent empty values

        // for some reason, label etc. are not allowed here (unlike for events)
        return [
            'metadata' => json_encode(
                array_map(function (MetadataField $field): array {
                    return $field->jsonSerialize();
                }, $this->metadata->getFields())
            )
        ];
    }
}
