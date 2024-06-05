<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\API;

use GuzzleHttp\HandlerStack;

/**
 * @author     Fabian Schmid <fabian@sr.solutions>
 */
final class Config
{
    private array $config;
    /**
     * @var array|mixed
     */
    private array $engage_config;

    public function __construct(
        HandlerStack $handler_stack,
        string $api_url,
        string $api_username,
        string $api_password,
        string $api_version = '',
        int $timeout = 0,
        int $connect_timeout = 0,
        ?string $presentation_node_url = null
    ) {
        $this->config = [
            'url' => rtrim(rtrim($api_url, '/'), '/api'),
            'username' => $api_username,
            'password' => $api_password,
            'version' => $api_version,
            'timeout' => ($timeout > 0 ? (intval($timeout) / 1000) : $timeout),
            'connect_timeout' => ($connect_timeout > 0 ? (intval($connect_timeout) / 1000) : $connect_timeout),
            'handler' => $handler_stack
        ];

        $this->engage_config = $this->config;
        if ($presentation_node_url !== null) {
            $this->engage_config['url'] = $presentation_node_url;
        }
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getEngageConfig(): array
    {
        return $this->engage_config;
    }
}
