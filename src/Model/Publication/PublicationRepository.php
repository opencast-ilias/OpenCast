<?php

namespace srag\Plugins\Opencast\Model\Publication;

use xoctException;

interface PublicationRepository
{
    /**
     * @param string $identifier
     * @return Publication[]
     */
    public function find(string $identifier): array;

    /**
     * @param string $identifier
     * @return Publication[]
     * @throws xoctException
     */
    public function fetch(string $identifier): array;
}
