<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Publication;

use xoctException;

interface PublicationRepository
{
    /**
     * @return Publication[]
     */
    public function find(string $identifier): array;

    /**
     * @return Publication[]
     * @throws xoctException
     */
    public function fetch(string $identifier): array;
}
