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

    /**
     * @param ACL $ACL
     */
    public function __construct(ACL $ACL)
    {
        $this->ACL = $ACL;
    }

    /**
     * @return ACL
     */
    public function getACL(): ACL
    {
        return $this->ACL;
    }


    public function jsonSerialize()
    {
        return ['acl' => json_encode($this->ACL)];
    }
}
