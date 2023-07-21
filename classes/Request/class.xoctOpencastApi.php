<?php
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use xoctOpencastApiHandlers;
use xoctOpencastApiDecorateProxy;
use xoctLog;
/**
 * Class xoctOpencastApi
 * This class integrates Opencast PHP Library into xoct.
 *
 * @copyright  2023 Farbod Zamani Boroujeni, ELAN e.V.
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class xoctOpencastApi
{
    /**
     * A flag indicating whether to return the value as array to each call from this class.
     * By default, response body of each call from OpencastApi is returned as stdClass object.
     * Therefore, this makes it possible to have returned values as array instead, by passing 'xoctOpencastApi::RETURN_ARRAY' as the last argument to each method call.
     * Usage example:
     * $array_data = xoctOpencastApi::getApi()->search->getEpisodes(['id' => $this->event->getIdentifier()], xoctOpencastApi::RETURN_ARRAY);
     */
    public const RETURN_ARRAY = 'return_array_flag';

    /**
     * @var array configuration parameters
     */
    protected static $config;

    /**
     * @var array configuration parameters
     */
    protected static $engage_config = [];

    /**
     * @var \OpencastApi\Opencast instance
     */
    public static $opencastApi;
    /**
     * @var \OpencastApi\Opencast instance
     */
    public static $opencastRestClient;

    /**
     * Initializes the class statics.
     *
     * @param string $api_url The API Url
     * @param string $api_username The API Username
     * @param string $api_password The API Password
     * @param string $api_version The API Version
     * @param int $timeout The request timeout miliseconds (OPTIONAL) (default 0)
     * @param int $connect_timeout The connection timeout miliseconds (OPTIONAL) (default 0)
     */
    public static function init(
        string $api_url,
        string $api_username,
        string $api_password,
        string $api_version = '',
        int $timeout = 0,
        int $connect_timeout = 0)
    {
        $handler_stack = xoctOpencastApiHandlers::getHandlerStack();
        self::$config = [
            'url' => rtrim(rtrim($api_url, '/'), '/api'),
            'username' => $api_username,
            'password' => $api_password,
            'version' => $api_version,
            'timeout' => ($timeout > 0 ? (intval($timeout) / 1000) : $timeout),
            'connect_timeout' => ($connect_timeout > 0 ? (intval($connect_timeout) / 1000) : $connect_timeout),
            'handler' => $handler_stack
        ];

        self::$engage_config = self::$config;
        if ($presentation_node_url = PluginConfig::getConfig(PluginConfig::F_PRESENTATION_NODE)) {
            self::$engage_config['url'] = $presentation_node_url;
        }

        // By default we don't need to activate ingest, hence we pass false to decorate services.
        // We deal with ingest on demand!
        self::$opencastApi = self::decorateApiServicesForXoct(false);
        self::$opencastRestClient = new \OpencastApi\Rest\OcRestClient(self::$config);
    }

    /**
     * It decorates the services provided by Opencast Api class to be customised for xoct specifically.
     * @param bool $activate_ingest whether to activate ingest service or not.
     * @return \OpencastApi\Opencast $opencastApi customised instance of \OpencastApi\Opencast
     */
    private static function decorateApiServicesForXoct(bool $activate_ingest = false): \OpencastApi\Opencast
    {
        $decorated_opencast_api = new \OpencastApi\Opencast(self::$config, self::$engage_config, $activate_ingest);
        $class_vars = get_object_vars($decorated_opencast_api);
        foreach ($class_vars as $name => $value) {
            $decorated_opencast_api->{$name} = new xoctOpencastApiDecorateProxy($decorated_opencast_api->{$name});
        }
        return $decorated_opencast_api;
    }

    /**
     * Gets the static OpencastApi instance.
     * @param bool $new Whether to return the static OpencastApi instance or create a new one.
     * @return \OpencastApi\Opencast $opencastApi instance of \OpencastApi\Opencast
     */
    public static function getApi(): \OpencastApi\Opencast
    {
        if (!self::$opencastApi) {
            PluginConfig::setApiSettings();
        }

        return self::$opencastApi;
    }

    /**
     * Gets the static OpencastRestClient instance.
     * @return \OpencastApi\Rest\OcRestClient $opencastRestClient instance of \OpencastApi\Rest\OcRestClient
     */
    public static function getRestClient(): \OpencastApi\Rest\OcRestClient
    {
        if (!self::$opencastRestClient) {
            PluginConfig::setApiSettings();
        }

        return self::$opencastRestClient;
    }

    /**
     * Toggle the ingest service of OpencastApi instance.
     * @param bool $activate whether to toggle the ingest service
     */
    public static function activateIngest(bool $activate)
    {
        if ($activate === true && !property_exists(self::$opencastApi, 'ingest')) {
            self::$opencastApi = self::decorateApiServicesForXoct($activate);
        } else if ($activate === false && property_exists(self::$opencastApi, 'ingest')) {
            self::$opencastApi = self::decorateApiServicesForXoct($activate);
        }
    }
}
