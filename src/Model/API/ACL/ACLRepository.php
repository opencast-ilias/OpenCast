<?php

namespace srag\Plugins\Opencast\Model\API\ACL;

use srag\Plugins\Opencast\Cache\Cache;
use xoctRequest;

class AclApiRepository
{

    /**
     * @var Cache
     */
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function find(string $identifier) : ACL
    {
        return $this->cache->get('event-acl-' . $identifier)
            ?? $this->fetch($identifier);
    }

    public function fetch(string $identifier): ACL
    {
        $data = json_decode(xoctRequest::root()->events($identifier)->acl()->get());
        $acl = ACL::fromResponse($data);
        $this->cache->set('event-acl-' . $identifier, $acl);
        return $acl;
    }
}
