<?php

namespace srag\Plugins\Opencast\Model\ACL;

interface ACLRepository
{
    public function find(string $identifier): ACL;

    public function fetch(string $identifier): ACL;
}