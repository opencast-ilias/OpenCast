<?php

namespace srag\Plugins\Opencast\API;

use srag\Plugins\Opencast\Model\Config\PluginConfig;
use OpencastApi\Opencast;
use OpencastApi\Rest\OcRestClient;

/**
 * Class srag\Plugins\Opencast\API\OpencastAPI
 * This class integrates Opencast PHP Library into xoct.
 *
 * @copyright  2023 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
interface API
{
    /**
     * Gets the static OpencastAPI instance.
     * @return Opencast $opencastApi instance of \OpencastAPI\Opencast
     */
    public static function routes(): Opencast;

    /**
     * Gets the static OpencastRestClient instance.
     * @return OcRestClient $opencastRestClient instance of \OpencastAPI\Rest\OcRestClient
     */
    public static function getRestClient(): OcRestClient;

    /**
     * Toggle the ingest service of OpencastAPI instance.
     * @param bool $activate whether to toggle the ingest service
     */
    public static function activateIngest(bool $activate): void;
}
