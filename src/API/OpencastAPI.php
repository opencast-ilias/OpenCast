<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\API;

use OpencastApi\Opencast;
use OpencastApi\Rest\OcRestClient;
use OpencastApi\Auth\JWT\OcJwtClaim;
use srag\Plugins\Opencast\Model\User\xoctUser;
use xoctLog;
use xoctException;

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
     * @var string jwt service flag for studio
     */
    public const JWT_SERVICE_STUDIO = 'jwt_service_studio';
    /**
     * @var string jwt service flag for editor
     */
    public const JWT_SERVICE_EDITOR = 'jwt_service_editor';
    /**
     * @var array allowed jwt services.
     */
    public const ALLOWED_JWT_SERVICES = [
        self::JWT_SERVICE_STUDIO,
        self::JWT_SERVICE_EDITOR,
    ];

    /**
     * @var string the jwt iframe src path url placeholder.
     */
    public const JWT_IFRAME_SRC_PATH_PLACEHOLDER = '/paella7/ui/watch.html';
    /**
     * @var Opencast
     */
    private Opencast $api;
    /**
     * @var OcRestClient
     */
    public OcRestClient $rest;
    /**
     * @readonly
     */
    private array $config;
    /**
     * @readonly
     */
    private array $engage_config;
    /**
     * @var xoctUser
     */
    private $user;
    /**
     * @var array Already generated JWTs for events
     */
    protected static $already_generated_events_jwts = [];
    /**
     * @var array Already generated JWTs for studio
     */
    protected static $already_generated_studio_jwts = [];
    /**
     * @var array Already generated JWTs for editor
     */
    protected static $already_generated_editor_jwts = [];
    /**
     * @var bool
     */
    private bool $has_jwt = false;

    public function __construct(Config $config)
    {
        global $DIC;
        $this->user = xoctUser::getInstance($DIC->user());
        $this->config = $config->getConfig();
        if (!empty($this->config['jwt'])) {
            $this->has_jwt = true;
        }
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
    ): string
    {
        // In case the configuration is off, then we return the url without injecting any jwt.
        if (!$this->has_jwt) {
            return $url;
        }

        // We now parse the incoming url, in order to process it and eventually attach a valid JWT.
        $parsed_url = parse_url($url);
        $query = $parsed_url['query'] ?? '';
        $parsed_query = [];
        parse_str($query, $parsed_query);

        // Starting with access token.
        $access_token = null;

        // If the url already has a token, we take it.
        if (!empty($parsed_query['jwt'])) {
            $access_token = $parsed_query['jwt'];
        }

        // If the token is somehow cached on repetitive actions.
        // We use those that are already exist to avoid unwanted process of generating tokens.
        if (empty($access_token) && isset(self::$already_generated_events_jwts[$identifier])) {
            $access_token = self::$already_generated_events_jwts[$identifier];
        }

        // We now decide if there is a need to generate new token.
        $need_new_access_token = empty($access_token);

        // If token already caught, we need to validate the token.
        if (!empty($access_token)) {
            $is_token_valid = $this->api->getRestJwtHandler()->validateToken($access_token);
            $has_similar_perms = false;
            $old_oc_jwt_claim = $this->api->getRestJwtHandler()->getOcJwtClaimFromTokenString($access_token);
            if (!empty($old_oc_jwt_claim)) {
                $has_similar_perms = $old_oc_jwt_claim->actionsMatchFor(OcJwtClaim::OC_EVENT, $identifier, $actions);
                $duration_is_valid = true;
                if (!empty($duration)) {
                    $new_expiry_date = OcJwtClaim::generateFormattedDateTimeObject($duration);
                    $old_expiry_date = $old_oc_jwt_claim->getExp();
                    if ($new_expiry_date->getTimestamp() > $old_expiry_date->getTimestamp()) {
                        $duration_is_valid = false;
                    }
                }
            }

            $need_new_access_token = !($is_token_valid && $has_similar_perms && $duration_is_valid);
        }

        if ($need_new_access_token) {
            try {
                $oc_claim = new OcJwtClaim();
                $event_acl = [
                    "$identifier" => $actions,
                ];
                $oc_claim->setEventAcls($event_acl);
                if (!empty($duration)) {
                    $expiry_formatted = OcJwtClaim::generateFormattedDateTimeObject($duration);
                    $oc_claim->setExp($expiry_formatted);
                }
                $access_token = $this->api->getRestJwtHandler()->issueToken($oc_claim);
                self::$already_generated_events_jwts[$identifier] = $access_token;
            } catch (\Throwable $th) {
                $xoctLog = xoctLog::getInstance();
                $xoctLog->write('ERROR: error while issuing JWT token: ' . $th->getMessage(), xoctLog::DEBUG_LEVEL_1);
                throw new xoctException(xoctException::JWT_TOKEN_ISSUE_FAILED, $th->getMessage());
            }
        }

        // Eventually, if we could have an access token, then we put it back in the jwt query param.
        if (!empty($access_token)) {
            $parsed_query['jwt'] = $access_token;
        }

        // Now, we convert the query params if exists.
        $query_built = http_build_query($parsed_query);
        if (!empty($query_built)) {
            $parsed_url['query'] = $query_built;
        }

        // Finally, we unparse the parsed url array and return it.
        return $this->unparseUrl($parsed_url);
    }

    /**
     * Issues a JWT for the external services such as Studio or Editor
     * These services need different type of claims to build and process.
     *
     * @param string $service the opencast external service name such as Editor or Studio
     * @return null|string null if JWT is disabled or not found, otherwise a proper JWT will be returned.
     */
    public function issueExternalServicesJwtFor(string $service): ?string
    {
        // In case the configuration is off, then we return the url without injecting any jwt.
        if (!$this->has_jwt) {
            return null;
        }

        if (!in_array($service, self::ALLOWED_JWT_SERVICES)) {
            return null;
        }

        // Starting with access token.
        $access_token = null;

        $cached_service_tokens = $this->getCachedJwtForService($service);

        // We use those that are already exist to avoid unwanted process of generating tokens.
        if (empty($access_token) && !empty($cached_service_tokens) &&
            isset($cached_service_tokens[$this->user->getIliasUserId()])) {
            $access_token = $cached_service_tokens[$this->user->getIliasUserId()];
        }

        // Prepare the claim info.
        $claim_info = [
            'sub'   => $this->user->getLogin(),
            'name'  => $this->user->getNamePresentation(false),
            'email' => $this->user->getEmail(),
        ];

        $roles = $this->user->getStudioAccessRoles();
        if ($service === self::JWT_SERVICE_EDITOR) {
            $roles = $this->user->getEditorAccessRoles();
        }
        $claim_info['roles'] = $roles;

        // We now decide if there is a need to generate new token.
        $need_new_access_token = empty($access_token);

        // If token already caught, we need to validate the token.
        if (!empty($access_token)) {
            $is_token_valid = $this->api->getRestJwtHandler()->validateToken($access_token);
            $has_similar_user_info = false;
            $old_oc_jwt_claim = $this->api->getRestJwtHandler()->getOcJwtClaimFromTokenString($access_token);
            if (!empty($old_oc_jwt_claim)) {
                $sub = $old_oc_jwt_claim->getSub();
                $name = $old_oc_jwt_claim->getName();
                $email = $old_oc_jwt_claim->getEmail();
                $roles = $old_oc_jwt_claim->getRoles();
                sort($roles);
                sort($claim_info['roles']);
                $has_similar_user_info = (!empty($sub) && $sub === $claim_info['sub']) &&
                                        (!empty($name) && $name === $claim_info['name']) &&
                                        (!empty($email) && $email === $claim_info['email']) &&
                                        (!empty($roles) && $roles === $claim_info['roles']);
            }
            $need_new_access_token = $is_token_valid && !$has_similar_user_info;
        }

        if ($need_new_access_token) {
            try {
                $oc_claim = new OcJwtClaim();
                $oc_claim = OcJwtClaim::createFromArray($claim_info);
                $access_token = $this->api->getRestJwtHandler()->issueToken($oc_claim);
                $this->cacheJwtForService($service, $access_token);
            } catch (\Throwable $th) {
                $xoctLog = xoctLog::getInstance();
                $xoctLog->write('ERROR: error while issuing JWT token: ' . $th->getMessage(), xoctLog::DEBUG_LEVEL_1);
                throw new xoctException(xoctException::JWT_TOKEN_ISSUE_FAILED, $th->getMessage());
            }
        }

        return $access_token;
    }

    /**
     * Tries to put the generated JWT into a built-in temp cache for each service.
     * @param string $service the service name
     * @param string $access_token JWT
     * @return void
     */
    private function cacheJwtForService(string $service, string $access_token): void
    {
        if ($service === self::JWT_SERVICE_STUDIO) {
            self::$already_generated_studio_jwts[$this->user->getIliasUserId()] = $access_token;
        } else if ($service === self::JWT_SERVICE_EDITOR) {
            self::$already_generated_editor_jwts[$this->user->getIliasUserId()] = $access_token;
        }
    }

    /**
     * Returns the cached JWT for the service.
     * @param string $service
     * @return null|array
     */
    private function getCachedJwtForService(string $service): ?array
    {
        $cache_set = [];
        if ($service === self::JWT_SERVICE_STUDIO) {
            $cache_set = self::$already_generated_studio_jwts;
        } else if ($service === self::JWT_SERVICE_EDITOR) {
            $cache_set = self::$already_generated_editor_jwts;
        }
        return $cache_set;
    }

    /**
     * Tries to detach the JWT query parameter from the URL.
     * @param string $url the url string
     * @return array a set of data including [{original url}, {the new filtered url without jwt}, {detached jwt}]
     */
    public function detachJwtFromUrl(string $url): array
    {
        $parsed_url = parse_url($url);
        $query = $parsed_url['query'] ?? '';
        $parsed_query = [];
        parse_str($query, $parsed_query);

        $original = $url;
        $jwt = null;

        if (!empty($parsed_query['jwt'])) {
            $jwt = $parsed_query['jwt'];
            unset($parsed_query['jwt']);
        }
        $query_built = http_build_query($parsed_query);
        unset($parsed_url['query']);
        if (!empty($query_built)) {
            $parsed_url['query'] = $query_built;
        }
        $filtered = $this->unparseUrl($parsed_url);

        return [$original, $filtered, $jwt];
    }

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
    private function unparseUrl(array $parsed_url): string
    {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * Refreshes an access token from an old access token, by maintaining its claim.
     *
     * @param string $token the old access token
     * @param int $duration optional duration, the configured one would be used by default.
     * @return null|string the new access token or null if old one is invalid
     */
    public function refreshToken(string $token, int $duration = 0): ?string
    {
        $oc_jwt_claim = $this->api->getRestJwtHandler()->getOcJwtClaimFromTokenString($token);
        if (empty($oc_jwt_claim)) {
            return null;
        }
        if (empty($duration)) {
            // We have to keep it short of not exists, due to the nature of JWT refresh mechanism.
            $duration = $this->config['jwt']['duration'] ?? 15;
        }
        $oc_jwt_claim->setExp(OcJwtClaim::generateFormattedDateTimeObject((int) $duration));
        $access_token = $this->api->getRestJwtHandler()->issueToken($oc_jwt_claim);
        return $access_token;
    }

    /**
     * Refreshes the access token for an event with basic read access.
     * @param string $identifier the video identifier
     * @param int $duration optional duration, the configured one would be used by default.
     * @return null|string a newly generate access token an event, or null if something goes wrong.
     */
    public function refreshTokenForEvent(string $identifier, int $duration = 0): ?string
    {
        $oc_claim = new OcJwtClaim();
        $event_acl = [
            "$identifier" => ['read'],
        ];
        $oc_claim->setEventAcls($event_acl);
        if (empty($duration)) {
            // We have to keep it short of not exists, due to the nature of JWT refresh mechanism.
            $duration = $this->config['jwt']['expiration'] ?? 15;
        }
        $expiry_formatted = OcJwtClaim::generateFormattedDateTimeObject($duration);
        $oc_claim->setExp($expiry_formatted);
        // To make sure the current search endpoint would have proper data,
        $access_token = $this->api->getRestJwtHandler()->issueToken($oc_claim);
        return $access_token;
    }

    /**
     * Checks if the JWT mechanism is activated.
     * @return bool
     */
    public function isJWTActivated(): bool
    {
        return $this->has_jwt;
    }

    /**
     * It gets a raw url and replace its path with /play/{id},
     * in order to be used as Iframe source tha can handle the JWT refresh token.
     *
     * @param string $url the raw url
     * @param string $event_id the event id
     * @return string the iframe JWT friendly source url ending with /play/{ID} or /paella7/ui/watch.html?id={ID}
     */
    public function makeJwtIframeSourceUrl(string $url, string $event_id): string
    {
        $parsed_url = parse_url($url);
        // This replace takes care of the case if we set the src placeholder as /play/{id}, but no effect on /paella...
        $path = str_replace('{id}', $event_id, self::JWT_IFRAME_SRC_PATH_PLACEHOLDER);
        $parsed_url['path'] = $path;
        // In case of having /paella.. as the placeholder we make sure that the id exists in the query string.
        if (str_starts_with(self::JWT_IFRAME_SRC_PATH_PLACEHOLDER, '/paella7')) {
            $query = !empty($parsed_url['query']) ? $parsed_url['query'] : '';
            $parsed_query = [];
            parse_str($query, $parsed_query);
            if (empty($parsed_query['id'])) {
                $parsed_query['id'] = $event_id;
            }
            $query_built = http_build_query($parsed_query);
            $parsed_url['query'] = $query_built;
        }
        return $this->unparseUrl($parsed_url);
    }
}
