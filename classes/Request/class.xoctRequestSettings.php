<?php

/**
 * Class xoctRequestSettings
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctRequestSettings
{
    /**
     * @var string
     */
    protected $api_base = '';
    /**
     * @var string
     */
    protected $api_version = '';

    /**
     * @return string
     */
    public function getApiBase()
    {
        return $this->api_base;
    }

    /**
     * @param string $api_base
     */
    public function setApiBase($api_base): void
    {
        $this->api_base = $api_base;
    }

    /**
     * @return string
     */
    public function getApiVersion()
    {
        return $this->api_version;
    }

    /**
     * @param string $api_version
     */
    public function setApiVersion($api_version): void
    {
        $this->api_version = $api_version;
    }
}
