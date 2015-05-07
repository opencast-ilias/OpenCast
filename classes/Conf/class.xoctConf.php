<?php

/**
 * Class xoctConf
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctConf {

	/**
	 * @param $config_key
	 */
	public function __construct($config_key) {
	}


	/**
	 * @var string
	 */
	public $config_key;
	/**
	 * @var string
	 */
	public $config_value;


	/**
	 * @return string
	 */
	public function getConfigKey() {
		return $this->config_key;
	}


	/**
	 * @param string $config_key
	 */
	public function setConfigKey($config_key) {
		$this->config_key = $config_key;
	}


	/**
	 * @return string
	 */
	public function getConfigValue() {
		return $this->config_value;
	}


	/**
	 * @param string $config_value
	 */
	public function setConfigValue($config_value) {
		$this->config_value = $config_value;
	}
}