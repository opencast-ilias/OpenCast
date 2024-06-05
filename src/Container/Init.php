<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Container;

use srag\Plugins\Opencast\API\API;
use srag\Plugins\Opencast\API\OpencastAPI;
use srag\Plugins\Opencast\API\Config;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\API\Handlers;
use srag\Plugins\Opencast\Model\Cache\Services;
use srag\Plugins\Opencast\Model\Cache\Config as CacheConfig;
use srag\Plugins\Opencast\Model\Event\EventAPIRepository;
use srag\Plugins\Opencast\DI\OpencastDIC;
use srag\Plugins\Opencast\Model\Series\SeriesAPIRepository;
use ILIAS\DI\HTTPServices;
use srag\Plugins\Opencast\Util\Locale\Translator;
use srag\Plugins\Opencast\UI\Integration\Integration;
use srag\Plugins\Opencast\Model\User\xoctUser;

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 *
 * @internal
 *
 * We use this dependency injection container at the moment as follows:
 * We put dependencies that we need in code into this container whenever possible and get it from there.
 * The convention is that we register the dependency with its FQDN in the container, if possible always with an
 * interface, which simplifies the exchange of the implementation.
 */
final class Init
{
    private static ?Container $container = null;

    /**
     *  @deprecated This method is currently used in many places in the plugin. However, the goal should be to use this
     *  initialization of the container a maximum of once and to inject the dependencies (or the entire container)
     *  everywhere as constructor arguments. in the end, this leaves only very few entry points at which the container
     *  must be effectively built and we get rid of all these static calls.
     */
    public static function init(?\ILIAS\DI\Container $ilias_container = null): Container
    {
        if (self::$container !== null) {
            return self::$container;
        }
        PluginConfig::setApiSettings();

        $opencast_container = new Container();
        $legacy_container = OpencastDIC::getInstance();

        // ILIAS Dependencies
        $opencast_container->glue(
            \ILIAS\DI\Container::class,
            fn(): ?\ILIAS\DI\Container => $ilias_container
        );

        $opencast_container->glue(HTTPServices::class, fn(): \ILIAS\HTTP\Services => $ilias_container->http());

        // Plugin Instance
        $opencast_container->glue(
            \ilOpenCastPlugin::class,
            static fn(): \ilOpenCastPlugin => \ilOpenCastPlugin::getInstance()
        );

        // Legacy Container
        $opencast_container->glue(
            OpencastDIC::class,
            static fn(): OpencastDIC => $legacy_container
        );

        // Translator
        $opencast_container->glue(
            Translator::class,
            static fn(): Translator => new Translator($opencast_container)
        );

        // UI Integration
        $opencast_container->glue(
            Integration::class,
            static fn(): Integration => new Integration(
                $opencast_container,
                $ilias_container->ui()->factory()
            )
        );

        // Plugin Dependencies
        $opencast_container->glue(Config::class, fn(): Config => new Config(
            Handlers::getHandlerStack(),
            PluginConfig::getConfig(PluginConfig::F_API_BASE) ?? 'https://stable.opencast.org/api',
            PluginConfig::getConfig(PluginConfig::F_CURL_USERNAME) ?? 'admin',
            PluginConfig::getConfig(PluginConfig::F_CURL_PASSWORD) ?? 'opencast',
            PluginConfig::getConfig(PluginConfig::F_API_VERSION) ?? '1.9.0',
            0,
            0,
            PluginConfig::getConfig(PluginConfig::F_PRESENTATION_NODE) ?? null
        ));

        $opencast_container->glue(API::class, fn(): OpencastAPI => new OpencastAPI($opencast_container[Config::class]));

        $opencast_container->glue(Services::class, function () use ($opencast_container): Services {
            $use_cache = (int) PluginConfig::getConfig(PluginConfig::F_ACTIVATE_CACHE);
            // map to caching settings
            switch ($use_cache) {
                case PluginConfig::CACHE_DISABLED:
                default:
                    $activated = false;
                    $adaptor = CacheConfig::PHPSTATIC;
                    break;
                case PluginConfig::CACHE_APCU:
                    $activated = true;
                    $adaptor = CacheConfig::APCU;
                    break;
                case PluginConfig::CACHE_DATABASE:
                    $activated = true;
                    $adaptor = CacheConfig::DATABASE;
                    break;
            }

            $config = new CacheConfig(
                $adaptor,
                $activated
            );

            return new Services(
                $config,
                $opencast_container[\ILIAS\DI\Container::class]->database()
            );
        });

        $opencast_container->glue(EventAPIRepository::class, fn(): EventAPIRepository => new EventAPIRepository(
            $opencast_container->get(Services::class),
            $legacy_container->get('event_parser'),
            $legacy_container->get('ingest_service')
        ));

        $opencast_container->glue(SeriesAPIRepository::class, fn(): SeriesAPIRepository => new SeriesAPIRepository(
            $opencast_container->get(Services::class),
            $legacy_container->get('series_parser'),
            $legacy_container->get('acl_utils'),
            $legacy_container->get('md_factory'),
            $legacy_container->get('md_parser')
        ));

        $opencast_container->glue(xoctUser::class, fn(): xoctUser => xoctUser::getInstance($ilias_container->user()));

        return self::$container = $opencast_container;
    }
}
