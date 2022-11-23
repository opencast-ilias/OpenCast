<?php

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use srag\Plugins\Opencast\Model\Config\PluginConfig;

require_once __DIR__ . '/../vendor/autoload.php';
require_once('./include/inc.ilias_version.php');
/**
 * Class xoct
 *
 * TODO: this is a helper class with all-static methods. I doubt that this is best practice, try and find a better place for this stuff at some point.
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoct
{
    public const ILIAS_50 = 50;
    public const ILIAS_51 = 51;
    public const ILIAS_52 = 52;
    public const ILIAS_53 = 53;
    public const ILIAS_54 = 54;
    public const ILIAS_6 = 60;
    public const ILIAS_7 = 70;
    public const MIN_ILIAS_VERSION = self::ILIAS_54;

    /**
     * @return int
     */
    public static function getILIASVersion()
    {
        if (self::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '6.999')) {
            return self::ILIAS_7;
        }
        if (self::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '5.4.999')) {
            return self::ILIAS_6;
        }
        if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '5.3.999')) {
            return self::ILIAS_54;
        }
        if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '5.2.999')) {
            return self::ILIAS_53;
        }
        if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '5.1.999')) {
            return self::ILIAS_52;
        }
        if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '5.0.999')) {
            return self::ILIAS_51;
        }
        if (ilComponent::isVersionGreaterString(ILIAS_VERSION_NUMERIC, '4.9.999')) {
            return self::ILIAS_50;
        }

        return 0;
    }

    /**
     * ilComponent's method doesn't work with ILIAS >6 yet because versions need to be of the format x.y.z
     *
     * @param string $v1
     * @param string $v2
     * @return bool
     */
    protected static function isVersionGreaterString(string $v1, string $v2): bool
    {
        return version_compare($v1, $v2, '>');
    }

    /**
     * @return bool
     */
    public static function isIlias54()
    {
        return self::getILIASVersion() >= self::ILIAS_54;
    }

    /**
     * @return bool
     */
    public static function isIlias6(): bool
    {
        return self::getILIASVersion() >= self::ILIAS_6;
    }

    public static function isApiVersionGreaterThan($api_version)
    {
        return version_compare(PluginConfig::getConfig(PluginConfig::F_API_VERSION), $api_version, '>=');
    }

    /**
     *
     */
    public static function isApi11()
    {
        return self::isApiVersionGreaterThan('v1.1.0');
    }

    /**
     *
     */
    public static function initILIAS()
    {
        chdir(self::getRootPath());
        require_once('./Services/Context/classes/class.ilContext.php');
        require_once('./Services/Authentication/classes/class.ilAuthFactory.php');
        $il_context_auth = ilAuthFactory::CONTEXT_WEB;
        $_COOKIE['ilClientId'] = $_SERVER['argv'][3];
        $_POST['username'] = $_SERVER['argv'][1];
        $_POST['password'] = $_SERVER['argv'][2];

        ilAuthFactory::setContext($il_context_auth);
        require_once('./include/inc.header.php');
    }

    /**
     * @return string
     */
    public static function getRootPath()
    {
        //		$override_file = dirname(__FILE__) . '/Configuration/root';
        //		if (is_file($override_file)) {
        //			$path = file_get_contents($override_file);
        //			$path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        //
        //			return $path;
        //		}

        $path = realpath(dirname(__FILE__) . '/../../../../../../../..');
        $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return $path;
    }

    public static function isIlias7(): bool
    {
        return self::getILIASVersion() >= self::ILIAS_7;
    }
}
