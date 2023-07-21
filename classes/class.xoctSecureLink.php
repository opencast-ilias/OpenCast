<?php

use srag\Plugins\Opencast\Model\Config\PluginConfig;
use xoctOpencastApi;
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
     * @param       $url
     *
     * @param null  $valid_until
     *
     * @param false $restict_ip
     *
     * @return mixed
     * @throws xoctException
     */
    protected static function sign($url, $valid_until = null, $restict_ip = false)
    {
        if (strpos($url, 'policy=') !== false && strpos($url, 'signature=') !== false) {
            // already signed, e.g. when presigning is active
            return $url;
        }
        if (!$url) {
            return '';
        }
        if (isset(self::$cache[$url])) {
            return self::$cache[$url];
        }

        $ip = ($restict_ip) ? self::getClientIP() : null;

        $data = xoctOpencastApi::getApi()->securityApi->sign($url, $valid_until, $ip);

        if ($data->error) {
            return '';
        }
        self::$cache[$url] = $data->url;

        return $data->url;
    }


    /**
     * @param $url
     *
     * @return mixed
     * @throws xoctException
     */
    public static function signThumbnail($url)
    {
        $duration = PluginConfig::getConfig(PluginConfig::F_SIGN_THUMBNAIL_LINKS_TIME);
        $valid_until = ($duration > 0) ? gmdate("Y-m-d\TH:i:s\Z", time() + $duration) : null;
        return self::sign($url, $valid_until, PluginConfig::getConfig(PluginConfig::F_SIGN_THUMBNAIL_LINKS_WITH_IP));
    }


    /**
     * @param $url
     *
     * @return mixed
     * @throws xoctException
     */
    public static function signAnnotation($url)
    {
        $duration = PluginConfig::getConfig(PluginConfig::F_SIGN_ANNOTATION_LINKS_TIME);
        $valid_until = ($duration > 0) ? gmdate("Y-m-d\TH:i:s\Z", time() + $duration) : null;
        return self::sign($url, $valid_until, PluginConfig::getConfig(PluginConfig::F_SIGN_ANNOTATION_LINKS_WITH_IP));
    }


    /**
     * @param   $url
     *
     * @param 0 $duration
     *
     * @return mixed
     * @throws xoctException
     */
    public static function signPlayer($url, $duration = 0)
    {
        $valid_until = null;
        if (PluginConfig::getConfig(PluginConfig::F_SIGN_PLAYER_LINKS_OVERWRITE_DEFAULT) && $duration > 0) {
            $duration_in_seconds = $duration / 1000;
            $additional_time_percent = PluginConfig::getConfig(PluginConfig::F_SIGN_PLAYER_LINKS_ADDITIONAL_TIME_PERCENT) / 100;
            $valid_until = gmdate("Y-m-d\TH:i:s\Z", time() + $duration_in_seconds + $duration_in_seconds * $additional_time_percent);
        }
        return self::sign($url, $valid_until, PluginConfig::getConfig(PluginConfig::F_SIGN_PLAYER_LINKS_WITH_IP));
    }


    /**
     * @param $url
     *
     * @return mixed
     * @throws xoctException
     */
    public static function signDownload($url)
    {
        $duration = PluginConfig::getConfig(PluginConfig::F_SIGN_DOWNLOAD_LINKS_TIME);
        $valid_until = ($duration > 0) ? gmdate("Y-m-d\TH:i:s\Z", time() + $duration) : null;
        return self::sign($url, $valid_until);
    }

    /**
     * @return mixed|string
     */
    protected static function getClientIP(): string
    {
        if ($_SERVER['HTTP_CLIENT_IP']) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }
        if ($_SERVER['HTTP_X_FORWARDED_FOR']) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if ($_SERVER['HTTP_X_FORWARDED']) {
            return $_SERVER['HTTP_X_FORWARDED'];
        }
        if ($_SERVER['HTTP_FORWARDED_FOR']) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        }
        if ($_SERVER['HTTP_FORWARDED']) {
            return $_SERVER['HTTP_FORWARDED'];
        }
        if ($_SERVER['REMOTE_ADDR']) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return '';
    }
}
