<?php

namespace srag\Plugins\Opencast\Model\Metadata;

use xoctException;

interface MetadataRepository
{
    /**
     * @throws xoctException
     */
    public function find(string $identifier): Metadata;

    /**
     * @param string $identifier
     * @return Metadata
     * @throws xoctException
     */
    public function fetch(string $identifier): Metadata;
}