<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\API;

use xoctLog;
use xoctException;
use GuzzleHttp\Client;
use OpencastApi\Rest\OcRest;

/**
 * Class srag\Plugins\Opencast\API\DecorateProxy
 * This is a decorative proxy to be wrapped around each OpencastAPI Service to handle responses for ILIAS OpenCast Plugin.
 *
 * @copyright  2023 Farbod Zamani Boroujeni, ELAN e.V.
 * @author     Farbod Zamani Boroujeni <zamani@elan-ev.de>
 */
class DecorateProxy
{
    public ?OcRest $object;
    public function __construct(?OcRest $client)
    {
        $this->object = $client;
    }

    public function __call($method, array $args)
    {
        // Prepare everything before calling the original method.
        $return_array = false;
        if (in_array(OpencastAPI::RETURN_ARRAY, $args, true)) {
            $index = array_search(OpencastAPI::RETURN_ARRAY, $args, true);
            if ($index !== false) {
                $return_array = true;
                unset($args[$index]);
            }
        }

        // Invoke original method on our proxied object.
        $response = call_user_func_array([$this->object, $method], $args);

        // Handle recursives.
        if ($response === $this->object) {
            return $this;
        }

        // After method invocation.
        $origin = [
            'class' => get_class($this->object),
            'method' => $method,
            'args' => $args
        ];

        if (isset($response['origin'])) {
            $origin['req_origin'] = $response['origin'];
        }

        // Digest the response to adapt with ILIAS OpenCast environment.
        return $this->digestResponse($response, $origin, $return_array);
    }

    public function digestResponse(array $response_array, array $origin, bool $return_array)
    {
        $body = $response_array['body'];
        $code = $response_array['code'];
        $reason = $response_array['reason'];
        $location = $response_array['location'];
        if ($code > 299 || $code === 0) {
            $req_origin = $origin['req_origin'] ?? [];
            xoctLog::getInstance()->write('ERROR ' . $code, xoctLog::DEBUG_LEVEL_1);
            xoctLog::getInstance()->write('Origin Class:' . $origin['class'], xoctLog::DEBUG_LEVEL_3);
            xoctLog::getInstance()->write('Origin Method:' . $origin['method'], xoctLog::DEBUG_LEVEL_3);
            $args = is_array($origin['args']) ? json_encode($origin['args']) : (string) $origin['args'];
            xoctLog::getInstance()->write('Origin Args:' . $args, xoctLog::DEBUG_LEVEL_3);

            $resp_orig_text = (new \ReflectionClass($origin['class']))->getShortName() . ' -> ' . $origin['method'];

            if (!empty($req_origin)) {
                $resp_orig_text .= ': [ (' . $req_origin['method'] . ') ' . $req_origin['path'] . ']';
                xoctLog::getInstance()->write('url:' . $req_origin['base'], xoctLog::DEBUG_LEVEL_3);
                xoctLog::getInstance()->write('path:' . $req_origin['path'], xoctLog::DEBUG_LEVEL_3);
                xoctLog::getInstance()->write('req method:' . $req_origin['method'], xoctLog::DEBUG_LEVEL_3);
                $params = json_encode($req_origin['params']) ?? '[]';
                xoctLog::getInstance()->write('req params:' . $params, xoctLog::DEBUG_LEVEL_3);
            }

            $resp_orig_text .= ' => ' . $reason;

            switch ($code) {
                case 403:
                    throw new xoctException(xoctException::API_CALL_STATUS_403, $resp_orig_text);
                    break;
                case 401:
                    throw new xoctException(xoctException::API_CALL_BAD_CREDENTIALS);
                    break;
                case 404:
                    throw new xoctException(xoctException::API_CALL_STATUS_404, $resp_orig_text);
                    break;
                case 409:
                    throw new xoctException(xoctException::API_CALL_STATUS_409, $resp_orig_text);
                    break;
                case 400:
                    throw new xoctException(xoctException::API_CALL_BAD_REQUEST, $resp_orig_text);
                    break;
                default:
                    throw new xoctException(xoctException::API_CALL_STATUS_500, $resp_orig_text);
                    break;
            }
        }

        return $return_array ? $this->parseResponseBodyToArray($body) : $body;
    }

    private function parseResponseBodyToArray($bodyObject)
    {
        return json_decode(json_encode($bodyObject), true);
    }
}
