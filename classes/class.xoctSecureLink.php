<?php

declare(strict_types=1);

use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\API\API;
use srag\Plugins\Opencast\Container\Init;

/**
 * Class xoctSecureLink
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctSecureLink
{
    /**
     * @var array
     */
    protected static $cache = [];

    /**
     * @deperecated we should get rid of static methods
     */
    protected static function sign(string $url, ?string $valid_until = null, ?bool $restict_ip = false)
    {
        $opencastContainer = Init::init();
        if (str_contains((string) $url, 'policy=') && str_contains((string) $url, 'signature=')) {
            // already signed, e.g. when presigning is active
            return $url;
        }
        if ($url === '' || $url === '0') {
            return '';
        }
        if (isset(self::$cache[$url])) {
            return self::$cache[$url];
        }

        $ip = ($restict_ip) ? self::getClientIP() : null;

        $data = $opencastContainer[API::class]->routes()->securityApi->sign($url, $valid_until ?? '', $ip);

        if ($data->error ?? false) {
            // We would only be able to log it here as error to avoid further confilicts.
            xoctLog::getInstance()->write(
                "[Error]: Signing link ($url) failed: {$data->error}",
                xoctLog::DEBUG_LEVEL_1
            );
            return $url;
        }
        self::$cache[$url] = $data->url;

        return $data->url;
    }

    public static function signThumbnail(string $url): string
    {
        $duration = PluginConfig::getConfig(PluginConfig::F_SIGN_THUMBNAIL_LINKS_TIME);
        $valid_until = ($duration > 0) ? gmdate("Y-m-d\TH:i:s\Z", time() + $duration) : null;
        return self::sign(
            $url,
            $valid_until,
            (bool) PluginConfig::getConfig(PluginConfig::F_SIGN_THUMBNAIL_LINKS_WITH_IP)
        );
    }

    public static function signAnnotation(string $url): string
    {
        $duration = PluginConfig::getConfig(PluginConfig::F_SIGN_ANNOTATION_LINKS_TIME);
        $valid_until = ($duration > 0) ? gmdate("Y-m-d\TH:i:s\Z", time() + $duration) : null;
        return self::sign(
            $url,
            $valid_until,
            (bool) PluginConfig::getConfig(PluginConfig::F_SIGN_ANNOTATION_LINKS_WITH_IP)
        );
    }

    public static function signPlayer(string $url, int $duration = 0): string
    {
        $valid_until = null;
        if (PluginConfig::getConfig(PluginConfig::F_SIGN_PLAYER_LINKS_OVERWRITE_DEFAULT) && $duration > 0) {
            $duration_in_seconds = $duration / 1000;
            $additional_time_percent = PluginConfig::getConfig(
                PluginConfig::F_SIGN_PLAYER_LINKS_ADDITIONAL_TIME_PERCENT
            ) / 100;
            $calculated_timestamp = time() + $duration_in_seconds + $duration_in_seconds * $additional_time_percent;
            $valid_until = gmdate(
                "Y-m-d\TH:i:s\Z",
                (int) $calculated_timestamp
            );
        }
        $url_path = parse_url((string) $url, PHP_URL_PATH);
        $extension = pathinfo($url_path, PATHINFO_EXTENSION);
        if ($extension === 'mp4' && !PluginConfig::getConfig(PluginConfig::F_SIGN_PLAYER_LINKS_MP4)) {
            return $url;
        }
        return self::sign(
            $url,
            $valid_until,
            (bool) PluginConfig::getConfig(PluginConfig::F_SIGN_PLAYER_LINKS_WITH_IP)
        );
    }

    public static function signDownload(string $url): string
    {
        $duration = PluginConfig::getConfig(PluginConfig::F_SIGN_DOWNLOAD_LINKS_TIME);
        $valid_until = ($duration > 0) ? gmdate("Y-m-d\TH:i:s\Z", (int) (time() + $duration)) : null;

        return self::sign($url, $valid_until);
    }

    protected static function getClientIP(): string
    {
        if ($_SERVER['HTTP_CLIENT_IP'] ?? null) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? null) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if ($_SERVER['HTTP_X_FORWARDED'] ?? null) {
            return $_SERVER['HTTP_X_FORWARDED'];
        }
        if ($_SERVER['HTTP_FORWARDED_FOR'] ?? null) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        }
        if ($_SERVER['HTTP_FORWARDED'] ?? null) {
            return $_SERVER['HTTP_FORWARDED'];
        }
        if ($_SERVER['REMOTE_ADDR'] ?? null) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return '';
    }
}
