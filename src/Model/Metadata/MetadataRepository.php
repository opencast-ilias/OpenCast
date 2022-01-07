<?php

namespace srag\Plugins\Opencast\Model\Metadata;

use xoctException;

interface MetadataRepository
{
    /**
     * @throws xoctException
     */
    public function findEventMD(string $identifier): Metadata;

    /**
     * @param string $identifier
     * @return Metadata
     * @throws xoctException
     */
    public function fetchEventMD(string $identifier): Metadata;

    public function findSeriesMD(string $identifier) : Metadata;

    public function fetchSeriesMD(string $identifier) : Metadata;
}