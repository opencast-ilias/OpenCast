<?php

namespace srag\Plugins\Opencast\Model\ACL;

use srag\Plugins\Opencast\Cache\Cache;
use xoctRequest;

class ACLApiRepository implements ACLRepository
{
    const CACHE_PREFIX = 'event-acl-';

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
        return $this->cache->get(self::CACHE_PREFIX . $identifier)
            ?? $this->fetch($identifier);
    }

    public function fetch(string $identifier): ACL
    {
        $data = json_decode(xoctRequest::root()->events($identifier)->acl()->get());
        $acl = ACL::fromResponse($data);
        $this->cache->set(self::CACHE_PREFIX . $identifier, $acl);
        return $acl;
    }
}
