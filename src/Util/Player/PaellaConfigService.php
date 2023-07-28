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
     * @return array{url: string, info: string, warn: bool}
     */
    public function getEffectivePaellaPlayerUrl(): array
    {
        $option = PluginConfig::getConfig(PluginConfig::F_PAELLA_OPTION);
        $default_path = PluginConfig::PAELLA_DEFAULT_PATH;

        $result = [
            'url' => $default_path,
            'info' => 'default config used',
            'warn' => false
        ];
        if ($option === PluginConfig::PAELLA_OPTION_URL) {
            $url = PluginConfig::getConfig(PluginConfig::F_PAELLA_URL);

            $result['url'] = $url;
            $result['info'] = 'config fetched from url';

            $reachable = $this->checkUrlReachable($url);
            if (!$reachable) {
                xoctLog::getInstance()->writeWarning('url for paella config unreachable: ' . $url);
                $result['url'] = $default_path;
                $result['info'] = 'url for paella config unreachable, fallback to default config';
                $result['warn'] = true;
            }
        }
        return $result;
    }

    /**
     * @param bool $live
     * @return array{url: string, info: string, warn: bool}
     */
    public function getPaellaPlayerThemeUrl(bool $live): array
    {
        $default_theme = PluginConfig::getConfig(PluginConfig::F_PAELLA_THEME);
        $default_theme_live = PluginConfig::getConfig(PluginConfig::F_PAELLA_THEME_LIVE);
        $default_theme_url = PluginConfig::PAELLA_DEFAULT_THEME;
        $default_theme_live_url = PluginConfig::PAELLA_DEFAULT_THEME_LIVE;

        $result = [
            'theme_url' => $default_theme_url,
            'theme_live_url' => $default_theme_live_url,
            'info' => 'default theme used',
        ];
        if ($live) {
            if ($default_theme_live === PluginConfig::PAELLA_OPTION_URL) {
                $url = PluginConfig::getConfig(PluginConfig::F_PAELLA_THEME_URL_LIVE);

                $result['theme_live_url'] = $url;
                $result['info'] = 'external live theme fetched from url';

                $reachable = $this->checkUrlReachable($url);
                if (!$reachable) {
                    xoctLog::getInstance()->writeWarning('url for paella live theme unreachable: ' . $url);
                    $result['theme_live_url'] = $default_theme_live_url;
                    $result['info'] = 'url for paella live theme unreachable, fallback to default live theme';
                }
            }
        } else {
            if ($default_theme === PluginConfig::PAELLA_OPTION_URL) {
                $url = PluginConfig::getConfig(PluginConfig::F_PAELLA_THEME_URL);

                $result['theme_url'] = $url;
                $result['info'] = 'external theme fetched from url';

                $reachable = $this->checkUrlReachable($url);
                if (!$reachable) {
                    xoctLog::getInstance()->writeWarning('url for paella theme unreachable: ' . $url);
                    $result['theme_url'] = $default_theme_url;
                    $result['info'] = 'url for paella theme unreachable, fallback to default theme';
                }
            }
        }
        return $result;
    }

    /**
     * @return string preview fallback url
     */
    public function getPaellaPlayerPreviewFallback()
    {
        $preview_fallback_http_path = ILIAS_HTTP_PATH . '/' . PluginConfig::PAELLA_DEFAULT_PREVIEW;
        $preview_fallback = PluginConfig::getConfig(PluginConfig::F_PAELLA_PREVIEW_FALLBACK);
        $url = $preview_fallback_http_path;
        if ($preview_fallback === PluginConfig::PAELLA_OPTION_URL) {
            $url = PluginConfig::getConfig(PluginConfig::F_PAELLA_PREVIEW_FALLBACK_URL);
            $reachable = $this->checkUrlReachable($url);
            if (!$reachable) {
                xoctLog::getInstance()->writeWarning('url for paella preview fallback unreachable: ' . $url);
            }
        }
        return $url;
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
