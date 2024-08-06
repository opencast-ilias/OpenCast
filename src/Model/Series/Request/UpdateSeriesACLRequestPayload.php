<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Series\Request;

use JsonSerializable;
use srag\Plugins\Opencast\Model\ACL\ACL;

class UpdateSeriesACLRequestPayload implements JsonSerializable
{
    public function __construct(private readonly ACL $ACL)
    {
    }

    public function getACL(): ACL
    {
        return $this->ACL;
    }

    /**
     * @return array{acl: string}
     */
    public function jsonSerialize(): mixed
    {
        return ['acl' => json_encode($this->ACL)];
    }
}
