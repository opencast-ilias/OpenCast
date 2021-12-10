<?php

namespace srag\Plugins\Opencast\Model\Event\Request;

use JsonSerializable;
use srag\Plugins\Opencast\Model\ACL\ACL;

class UpdateEventACLRequestPayload implements JsonSerializable
{
    /**
     * @var ACL
     */
    private $acl;

    /**
     * @param ACL $acl
     */
    public function __construct(ACL $acl)
    {
        $this->acl = $acl;
    }


    public function jsonSerialize()
    {
        return ['acl' => json_encode($this->acl)];
    }
}