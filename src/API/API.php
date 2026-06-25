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

    /**
     * Attaches the JWT into the url of the Static File.
     * It first parses the static file url and tries find the already existing JWT, if not found or the current one is not valid,
     * then it creates a new one and attaches it to the url as "jwt" query params.
     * It is clever enough to maintain other query params as well as avoiding redundant jwt param.
     *
     * @param string $url The static file url
     * @param string $identifier The video identifier
     * @param array $actions The acl actions needed to apply for the access
     * @param int $duration a custom token expiry duration "in seconds" to be replaced by the one configured.
     *
     * @return string the static file url with attached jwt, if the feature is disabled the url is returned unchanged.
     */
    public function attachJwtIntoStaticFileUrlForEvent(
        string $url,
        string $identifier,
        array $actions = ['read'],
        int $duration = 0
    ): string;

    /**
     * Issues a JWT for the external services such as Studio or Editor
     * These services need different type of claims to build and process.
     *
     * @param string $service the opencast external service name such as Editor or Studio
     * @param ?string $event_id the event identifier
     * @return null|string null if JWT is disabled or not found, otherwise a proper JWT will be returned.
     */
    public function issueExternalServicesJwtFor(string $service, ?string $event_id = null): ?string;

    /**
     * Tries to detach the JWT query parameter from the URL.
     * @param string $url the url string
     * @return array a set of data including [{original url}, {the new filtered url without jwt}, {detached jwt}]
     */
    public function detachJwtFromUrl(string $url): array;

    /**
     * Checks if the JWT mechanism is activated.
     * @return bool
     */
    public function isJWTActivated(): bool;

    /**
     * Refreshes an access token from an old access token, by maintaining its claim.
     *
     * @param string $token the old access token
     * @param int $duration optional duration
     * @return null|string the new access token or null if old one is invalid
     */
    public function refreshToken(string $token, int $duration = 0): ?string;

    /**
     * Refreshes the access token for an event with basic read access.
     * @param string $identifier the video identifier
     * @param int $duration optional duration, the configured one would be used by default.
     * @return null|string a newly generate access token an event, or null if something goes wrong.
     */
    public function refreshTokenForEvent(string $identifier, int $duration = 0): ?string;

    /**
     * It gets a raw url and replace its path with /play/{id},
     * in order to be used as Iframe source tha can handle the JWT refresh token.
     *
     * @param string $url the raw url
     * @param string $event_id the event id
     * @return string the iframe JWT friendly source url ending with /play/{ID}
     */
    public function makeJwtIframeSourceUrl(string $url, string $event_id): string;

    /**
     * Reconstructs a URL string from its parsed components.
     *
     * This method takes an associative array similar to the output of `parse_url()`
     * and rebuilds the original URL string, including scheme, host, port, user, password,
     * path, query, and fragment if they are present.
     *
     * @param array $parsed_url An associative array containing parts of a URL
     *                          (keys: scheme, host, port, user, pass, path, query, fragment).
     *
     * @return string The reconstructed URL.
     */
    public function unparseUrl(array $parsed_url): string;
}
