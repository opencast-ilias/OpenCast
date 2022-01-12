<?php

namespace srag\Plugins\Opencast\Model\Series\Request;

use InvalidArgumentException;
use JsonSerializable;
use srag\Plugins\Opencast\Model\ACL\ACL;
use srag\Plugins\Opencast\Model\Metadata\Metadata;

class UpdateSeriesRequestPayload implements JsonSerializable
{
    /**
     * @var ?Metadata
     */
    protected $metadata;
    /**
     * @var ?ACL
     */
    private $ACL;

    /**
     * @param Metadata|null $metadata
     * @param ACL|null $ACL
     */
    public function __construct(?Metadata $metadata, ?ACL $ACL)
    {
        if (is_null($metadata) && is_null($ACL)) {
            throw new InvalidArgumentException("UpdateSeriesRequestPayload must contain either metadata or ACL");
        }
        $this->metadata = $metadata;
        $this->ACL = $ACL;
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
        $arr = [];
        if (!is_null($this->metadata)) {
            $arr['metadata'] = json_encode([$this->metadata->jsonSerialize()]);
        }
        if (!is_null($this->ACL)) {
            $arr['acl'] = json_encode($this->ACL->jsonSerialize());
        }
        return $arr;
    }
}