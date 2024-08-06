<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Series\Request;

use JsonSerializable;
use srag\Plugins\Opencast\Model\ACL\ACL;
use srag\Plugins\Opencast\Model\Metadata\Metadata;

class CreateSeriesRequestPayload implements JsonSerializable
{
    use SanitizeSeriesMetadata;

    /**
     * @param int $theme
     */
    public function __construct(private Metadata $metadata, private ACL $acl)
    {
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getAcl(): ACL
    {
        return $this->acl;
    }

    /**
     * @return array{metadata: string, acl: string}
     */
    public function jsonSerialize(): mixed
    {
        $this->saniziteMetadataFields($this->metadata->getFields()); // to prevent empty values
        return [
            'metadata' => json_encode([$this->metadata->jsonSerialize()]),
            'acl' => json_encode($this->acl),
        ];
    }
}
