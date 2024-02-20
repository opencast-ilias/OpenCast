<?php

declare(strict_types=1);

/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use srag\Plugins\Opencast\Model\Config\PluginConfig;

/**
 * Class xoctInternalAPI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctInternalAPI
{
    /**
     * @var self
     */
    protected static $instance;

    /**
     * xoctInternalAPI constructor.
     */
    public function __construct()
    {
        PluginConfig::setApiSettings();
    }

    /**
     * @return xoctInternalAPI
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function series(): \xoctSeriesAPI
    {
        return xoctSeriesAPI::getInstance();
    }

    public function events(): \xoctEventAPI
    {
        return xoctEventAPI::getInstance();
    }
}
