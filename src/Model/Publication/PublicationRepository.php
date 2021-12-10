<?php

namespace srag\Plugins\Opencast\Model\Publication;

use xoctException;
use xoctPublication;

interface PublicationRepository
{
    /**
     * @param string $identifier
     * @return xoctPublication[]
     */
    public function find(string $identifier): array;

    /**
     * @param string $identifier
     * @return xoctPublication[]
     * @throws xoctException
     */
    public function fetch(string $identifier): array;
}