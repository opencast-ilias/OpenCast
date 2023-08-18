<?php

namespace srag\Plugins\Opencast\Model\Series\Request;

use JsonSerializable;
use srag\Plugins\Opencast\Model\ACL\ACL;

class UpdateSeriesACLRequestPayload implements JsonSerializable
{
    /**
     * @var ACL
     */
    private $ACL;

    public function __construct(ACL $ACL)
    {
        $this->ACL = $ACL;
    }

    public function getACL(): ACL
    {
        return $this->ACL;
    }

    /**
     * @return array{acl: string}
     */
    public function jsonSerialize()
    {
        return ['acl' => json_encode($this->ACL, JSON_THROW_ON_ERROR)];
    }
}
