<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\Util\FileTransfer\PaellaConfigStorageService;

/**
 * this factory is unnecessary by now, but I didn't have time to refactor it away..
 */
class PaellaConfigServiceFactory
{
    /**
     * @var PaellaConfigStorageService
     */
    private $storageService;

    public function __construct(PaellaConfigStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    public function get(): PaellaConfigService
    {
        return new PaellaConfigService($this->storageService);
    }
}
