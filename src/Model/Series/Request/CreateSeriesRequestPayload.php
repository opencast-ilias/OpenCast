<?php

namespace srag\Plugins\Opencast\Model\Series\Request;

use JsonSerializable;
use srag\Plugins\Opencast\Model\ACL\ACL;
use srag\Plugins\Opencast\Model\Metadata\Metadata;

class CreateSeriesRequestPayload implements JsonSerializable
{
    /**
     * @var Metadata
     */
    private $metadata;
    /**
     * @var ACL
     */
    private $acl;

    /**
     * @param Metadata $metadata
     * @param ACL $acl
     * @param int $theme
     */
    public function __construct(Metadata $metadata, ACL $acl)
    {
        $this->metadata = $metadata;
        $this->acl = $acl;
    }

    /**
     * @return Metadata
     */
    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    /**
     * @return ACL
     */
    public function getAcl(): ACL
    {
        return $this->acl;
    }

    public function jsonSerialize()
    {
        return [
            'metadata' => json_encode([$this->metadata->jsonSerialize()]),
            'acl' => json_encode($this->acl),
        ];
    }
}
