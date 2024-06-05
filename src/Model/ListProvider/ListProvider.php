<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\ListProvider;

use xoctException;
use srag\Plugins\Opencast\API\OpencastAPI;
use srag\Plugins\Opencast\API\API;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Container\Init;

/**
 * Class ListProvider
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class ListProvider
{
    /**
     * @var API
     */
    protected $api;

    protected $required_api_version = 'v1.10.0';

    /**
     * @throws xoctException
     */
    public function __construct()
    {
        $opencastContainer = Init::init();
        $this->api = $opencastContainer[API::class];
    }

    /**
     * Checks the required API version
     *
     * @return boolean true if the required API version matches the required version
     */
    private function mandatoryVersionCheck(): bool
    {
        $api_version = PluginConfig::getConfig(PluginConfig::F_API_VERSION);

        return ($api_version && version_compare($api_version, $this->required_api_version, '>='));
    }

    /**
     * Gets the list of providers that is available in Opencast to extract their lists from.
     *
     * @return array the provides list
     *
     * @throws xoctException
     */
    public function getProviders(): array
    {
        if ($this->mandatoryVersionCheck() === false) {
            return [];
        }
        $providers = $this->api->routes()->listProvidersApi->getProviders(OpencastAPI::RETURN_ARRAY);
        if (is_array($providers) && isset($providers['available'])) {
            return count($providers['available']) === 1 ? reset($providers['available']) : $providers['available'];
        }

        if (is_array($providers) && count($providers) > 0) {
            return count($providers) === 1 ? reset($providers) : $providers;
        }

        if (is_object($providers) && property_exists($providers, 'available')) {
            return count($providers->available) === 1 ? reset($providers->available) : $providers->available;
        }
        return [];
    }

    /**
     * Checks if the requested source list is actually provided by Opencast.
     *
     * @param string $source the list source (mostly LANGUAGES or LICENSES)
     *
     * @return bool true if the source list exists, false otherwise
     */
    public function hasList($source): bool
    {
        $found = array_filter($this->getProviders(), function ($provider) use ($source) {
            return strpos($provider, strtoupper($source)) !== false;
        });
        return count($found) == 1;
    }

    /**
     * Gets the value list of a provider source from Opencast
     *
     * @param string $source the list source (mostly LANGUAGES or LICENSES)
     *
     * @return array the value list of a provider source
     *
     * @throws xoctException
     */
    public function getList($source): array
    {
        if ($this->mandatoryVersionCheck() === false) {
            return [];
        }

        $source = strtoupper($source);

        $list = $this->api->routes()->listProvidersApi->getList($source, OpencastAPI::RETURN_ARRAY);
        if (is_array($list) && isset($list['available'])) {
            return $list['available'];
        }

        if (is_array($list) && count($list) > 0) {
            return $list;
        }

        if (is_object($list) && property_exists($list, 'available')) {
            return $list->available;
        }
        return [];
    }
}
