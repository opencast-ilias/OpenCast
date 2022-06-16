<?php

namespace srag\Plugins\Opencast\Util\Player;

use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Util\FileTransfer\PaellaConfigStorageService;
use xoctLog;

class PaellaConfigService
{
    /**
     * @var PaellaConfigStorageService
     */
    private $storageService;

    public function __construct(PaellaConfigStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * @param bool $live
     * @return array{url: string, info: string, warn: bool}
     */
    public function getEffectivePaellaPlayerUrl(bool $live): array
    {
        $option = $live ? PluginConfig::getConfig(PluginConfig::F_PAELLA_OPTION_LIVE)
            : PluginConfig::getConfig(PluginConfig::F_PAELLA_OPTION);
        $default_path = $live ? PluginConfig::PAELLA_DEFAULT_PATH : PluginConfig::PAELLA_DEFAULT_PATH;
        switch ($option) {
            case PluginConfig::PAELLA_OPTION_URL:
                $url = $live ? PluginConfig::getConfig(PluginConfig::F_PAELLA_URL_LIVE)
                    : PluginConfig::getConfig(PluginConfig::F_PAELLA_URL);
                $reachable = $this->checkUrlReachable($url);
                if (!$reachable) {
                    xoctLog::getInstance()->writeWarning('url for paella config unreachable: ' . $url);
                    return ['url' => $default_path,
                        'info' => 'url for paella config unreachable, fallback to default config',
                        'warn' => true];
                }
                return ['url' => $url, 'info' => 'config fetched from url', 'warn' => false];
            case PluginConfig::PAELLA_OPTION_DEFAULT:
            default:
                return ['url' => $default_path, 'info' => 'default config used', 'warn' => false];
        }
    }

    public function checkUrlReachable(string $url): bool
    {
        $file_headers = @get_headers($url);
        if (!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
            return false;
        }
        return true;
    }
}
