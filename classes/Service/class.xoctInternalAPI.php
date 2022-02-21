<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

use srag\Plugins\Opencast\Model\Config\PluginConfig;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Class xoctInternalAPI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctInternalAPI {

	/**
	 * @var self
	 */
	protected static $instance;


	/**
	 * xoctInternalAPI constructor.
	 */
	public function __construct() {
		PluginConfig::setApiSettings();
	}


	/**
	 * @return xoctInternalAPI
	 */
	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * @return xoctSeriesAPI
	 */
	public function series() {
		return xoctSeriesAPI::getInstance();
	}


	/**
	 * @return xoctEventAPI
	 */
	public function events() {
		return xoctEventAPI::getInstance();
	}
}