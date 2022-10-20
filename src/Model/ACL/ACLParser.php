<?php

namespace srag\Plugins\Opencast\Model\ACL;

class ACLParser
{
    public function parseAPIResponse(array $response): ACL
    {
        $entries = [];
        foreach ($response as $data) {
            $entries[] = ACLEntry::fromArray((array) $data);
        }
        return new ACL($entries);
    }
}
