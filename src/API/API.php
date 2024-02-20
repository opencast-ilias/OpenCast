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
interface API
{
    /**
     * Gets the static OpencastAPI instance.
     * @return Opencast $api instance of \OpencastAPI\Opencast
     */
    public function routes(): Opencast;

    /**
     * Gets the static OpencastRestClient instance.
     * @return OcRestClient $opencastRestClient instance of \OpencastAPI\Rest\OcRestClient
     */
    public function rest(): OcRestClient;

    /**
     * Toggle the ingest service of OpencastAPI instance.
     * @param bool $activate whether to toggle the ingest service
     */
    public function activateIngest(bool $activate): void;
}
