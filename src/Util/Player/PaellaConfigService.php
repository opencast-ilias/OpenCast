<?php

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Util\FileTransfer\PaellaConfigStorageService;
use xoctLog;

class PaellaConfigService
{
    /**
     * @var ObjectSettings
     */
    private $objectSettings;
    /**
     * @var PaellaConfigStorageService
     */
    private $storageService;

    public function __construct(ObjectSettings $objectSettings, PaellaConfigStorageService $storageService)
    {
        $this->objectSettings = $objectSettings;
        $this->storageService = $storageService;
    }

    public function checkAndUpdatePaellaConfig(ObjectSettings $newObjectSettings)
    {
        // check if existing file should be deleted
        if ($this->objectSettings->getPaellaPlayerFileId() && !$newObjectSettings->getPaellaPlayerFileId()) {
            $this->storageService->delete($this->objectSettings->getPaellaPlayerFileId());
        }
        if ($this->objectSettings->getPaellaPlayerLiveFileId() && !$newObjectSettings->getPaellaPlayerLiveFileId()) {
            $this->storageService->delete($this->objectSettings->getPaellaPlayerLiveFileId());
        }
        // check if file should be fetched from url
        // only necessary if we want to cache the config for the 'url' option
//        if ($newObjectSettings->getPaellaPlayerOption() === ObjectSettings::PAELLA_OPTION_URL) {
//            $this->storageService->fetchFromUrlAndStore($newObjectSettings->getPaellaPlayerUrl());
//        }
//        if ($newObjectSettings->getPaellaPlayerLiveOption() === ObjectSettings::PAELLA_OPTION_URL) {
//            $this->storageService->fetchFromUrlAndStore($newObjectSettings->getPaellaPlayerLiveUrl());
//        }
    }

    /**
     * @param bool $live
     * @return array{url: string, info: string, warn: bool}
     */
    public function getEffectivePaellaPlayerUrl(bool $live) : array
    {
        $objectSettings = $this->objectSettings;
        $option = $live ? $objectSettings->getPaellaPlayerLiveOption() : $objectSettings->getPaellaPlayerOption();
        $default_path = $live ? ObjectSettings::DEFAULT_PATH_LIVE : ObjectSettings::DEFAULT_PATH;
        switch ($option) {
            case ObjectSettings::PAELLA_OPTION_URL:
                $url = $live ? $objectSettings->getPaellaPlayerLiveUrl() : $objectSettings->getPaellaPlayerUrl();
                $reachable = $this->checkUrlReachable($url);
                if (!$reachable) {
                    xoctLog::getInstance()->writeWarning('url for paella config unreachable: ' . $url);
                    return ['url' => $default_path,
                        'info' => 'url for paella config unreachable, fallback to default config',
                        'warn' => true];
                }
                return ['url' => $url, 'info' => 'config fetched from url', 'warn' => false];
            case ObjectSettings::PAELLA_OPTION_FILE:
                $path = $live ? $objectSettings->getPaellaPlayerLiveFileId() : $objectSettings->getPaellaPlayerFileId();
                // fallback to default if file doesn't exist
                return ($path && $this->storageService->exists($path)) ?
                    ['url' => $this->storageService->getWACSignedPath($path), 'info' => 'uploaded config used', 'warn' => false]
                    : ['url' => $default_path, 'info' => 'uploaded config not found, fallback to default config', 'warn' => true];
            case ObjectSettings::PAELLA_OPTION_DEFAULT:
            default:
                return ['url' => $default_path, 'info' => 'default config used', 'warn' => false];
        }
    }

    public function checkUrlReachable(string $url) : bool
    {
        $file_headers = @get_headers($url);
        if(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
            return false;
        }
        return true;
    }
    
}