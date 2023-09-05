<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 */

declare(strict_types=1);

namespace srag\Plugins\Opencast\Container;

use srag\Plugins\Opencast\API\API;
use srag\Plugins\Opencast\API\OpencastAPI;
use srag\Plugins\Opencast\API\Config;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\API\Handlers;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 *
 * @internal
 *
 * We use this dependency injection container at the moment as follows:
 * We put dependencies that we need in code into this container whenever possible and get it from there. The convention is that we register the dependency with its FQDN in the container, if possible always with an interface, which simplifies the exchange of the implementation.
 */
final class Init
{
    /**
     * @var Container|null
     */
    private static $container = null;

    public static function init(?\ILIAS\DI\Container $ilias_container = null): Container
    {
        if (self::$container !== null) {
            return self::$container;
        }
        $opencast_container = new Container();

        // ILIAS Dependencies
        $opencast_container->glue(
            \ILIAS\DI\Container::class,
            function () use ($ilias_container) {
                return $ilias_container;
            }
        );

        // Plugin Instance
        $opencast_container->glue(
            \ilOpenCastPlugin::class,
            static function () {
                return \ilOpenCastPlugin::getInstance();
            }
        );

        // Plugin Dependencies
        $opencast_container->glue(Config::class, function () use ($opencast_container) {
            return new Config(
                Handlers::getHandlerStack(),
                PluginConfig::getConfig(PluginConfig::F_API_BASE) ?? 'https://stable.opencast.org/api',
                PluginConfig::getConfig(PluginConfig::F_CURL_USERNAME) ?? 'admin',
                PluginConfig::getConfig(PluginConfig::F_CURL_PASSWORD) ?? 'opencast',
                PluginConfig::getConfig(PluginConfig::F_API_VERSION) ?? '1.9.0'
            );
        });

        $opencast_container->glue(API::class, function () use ($opencast_container) {
            return new OpencastAPI($opencast_container[Config::class]);
        });

        return self::$container = $opencast_container;
    }
}
