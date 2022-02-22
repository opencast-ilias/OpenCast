<?php

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Util\FileTransfer\PaellaConfigStorageService;

class PaellaConfigServiceFactory
{
    /**
     * @var PaellaConfigStorageService
     */
    private $storageService;

    /**
     * @param PaellaConfigStorageService $storageService
     */
    public function __construct(PaellaConfigStorageService $storageService)
    {
        $this->storageService = $storageService;
    }


    public function forObject(ObjectSettings $objectSettings) : PaellaConfigService
    {
        return new PaellaConfigService($objectSettings, $this->storageService);
    }
}