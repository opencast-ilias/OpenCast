<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\API;

use OpencastApi\Opencast;
use OpencastApi\Rest\OcRestClient;

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

    private Opencast $api;
    public OcRestClient $rest;
    /**
     * @readonly
     */
    private array $config;
    /**
     * @readonly
     */
    private array $engage_config;

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
        $this->rest = new OcRestClient($this->config);
    }

    /**
     * It decorates the services provided by Opencast Api class to be customised for xoct specifically.
     * @param bool $activate_ingest whether to activate ingest service or not.
     * @return Opencast $api customised instance of \OpencastAPI\Opencast
     */
    private function decorateApiServicesForXoct(bool $activate_ingest = false): Opencast
    {
        $decorated_opencast_api = new Opencast($this->config, $this->engage_config, $activate_ingest);
        $class_vars = get_object_vars($decorated_opencast_api);
        foreach (array_keys($class_vars) as $name) {
            $decorated_opencast_api->{$name} = new DecorateProxy($decorated_opencast_api->{$name});
        }
        return $decorated_opencast_api;
    }

    /**
     * Gets the static OpencastAPI instance.
     * @param bool $new Whether to return the static OpencastAPI instance or create a new one.
     * @return Opencast $api instance of \OpencastAPI\Opencast
     */
    public function routes(): Opencast
    {
        return $this->api;
    }

    /**
     * Gets the static OpencastRestClient instance.
     * @return OcRestClient $opencastRestClient instance of \OpencastAPI\Rest\OcRestClient
     */
    public function rest(): OcRestClient
    {
        return $this->rest;
    }

    /**
     * Toggle the ingest service of OpencastAPI instance.
     * @param bool $activate whether to toggle the ingest service
     */
    public function activateIngest(bool $activate): void
    {
        if ($activate && ($this->api->ingest->object ?? null) === null) {
            $this->api = $this->decorateApiServicesForXoct($activate);
        } elseif ($activate === false && ($this->api->ingest->object ?? null) !== null) {
            $this->api = $this->decorateApiServicesForXoct($activate);
        }
    }
}
