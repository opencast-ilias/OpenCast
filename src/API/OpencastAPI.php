<?php

namespace srag\Plugins\Opencast\API;

use srag\Plugins\Opencast\Model\Config\PluginConfig;

/**
 * Class srag\Plugins\Opencast\API\OpencastAPI
 * This class integrates Opencast PHP Library into xoct.
 *
 * @copyright  2023 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class OpencastAPI implements API
{
    /**
     * A flag indicating whether to return the value as array to each call from this class.
     * By default, response body of each call from OpencastAPI is returned as stdClass object.
     * Therefore, this makes it possible to have returned values as array instead, by passing 'srag\Plugins\Opencast\API\OpencastAPI::RETURN_ARRAY' as the last argument to each method call.
     * Usage example:
     * $array_data = $opencastContainer[API::class]->routes()->search->getEpisodes(['id' => $this->event->getIdentifier()], srag\Plugins\Opencast\API\OpencastAPI::RETURN_ARRAY);
     */
    public const RETURN_ARRAY = 'return_array_flag';


    /**
     * @var \OpencastApi\Opencast instance
     */
    private $api;
    /**
     * @var \OpencastApi\Rest\OcRestClient instance
     */
    public $rest;
    /**
     * @var array
     */
    private $config;
    /**
     * @var array
     */
    private $engage_config;

    public function __construct(Config $config)
    {
        $this->config = $config->getConfig();
        $this->engage_config = $config->getEngageConfig();
        $this->init();
    }

    private function init(): void
    {
        // By default we don't need to activate ingest, hence we pass false to decorate services.
        // We deal with ingest on demand!
        $this->api = $this->decorateApiServicesForXoct(false);
        $this->rest = new \OpencastApi\Rest\OcRestClient($this->config);
    }

    /**
     * It decorates the services provided by Opencast Api class to be customised for xoct specifically.
     * @param bool $activate_ingest whether to activate ingest service or not.
     * @return \OpencastApi\Opencast $api customised instance of \OpencastAPI\Opencast
     */
    private function decorateApiServicesForXoct(bool $activate_ingest = false): \OpencastApi\Opencast
    {
        $decorated_opencast_api = new \OpencastApi\Opencast($this->config, $this->engage_config, $activate_ingest);
        $class_vars = get_object_vars($decorated_opencast_api);
        foreach ($class_vars as $name => $value) {
            $decorated_opencast_api->{$name} = new DecorateProxy($decorated_opencast_api->{$name});
        }
        return $decorated_opencast_api;
    }

    /**
     * Gets the static OpencastAPI instance.
     * @param bool $new Whether to return the static OpencastAPI instance or create a new one.
     * @return \OpencastApi\Opencast $api instance of \OpencastAPI\Opencast
     */
    public function routes(): \OpencastApi\Opencast
    {
        return $this->api;
    }

    /**
     * Gets the static OpencastRestClient instance.
     * @return \OpencastApi\Rest\OcRestClient $opencastRestClient instance of \OpencastAPI\Rest\OcRestClient
     */
    public function rest(): \OpencastApi\Rest\OcRestClient
    {
        return $this->rest;
    }

    /**
     * Toggle the ingest service of OpencastAPI instance.
     * @param bool $activate whether to toggle the ingest service
     */
    public function activateIngest(bool $activate): void
    {
        if ($activate === true && $this->api->ingest->object === null) {
            $this->api = $this->decorateApiServicesForXoct($activate);
        } elseif ($activate === false && $this->api->ingest->object !== null) {
            $this->api = $this->decorateApiServicesForXoct($activate);
        }
    }
}
