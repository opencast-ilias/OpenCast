<?php

/**
 * Class xoctBase
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctBase {

	/**
	 *
	 */
	public function __construct() {
	}


	/**
	 * @var string
	 */
	public $api_version;
	/**
	 * @var string
	 */
	public $organisation;


	/**
	 * @return string
	 */
	public function getApiVersion() {
		return $this->api_version;
	}


	/**
	 * @param string $api_version
	 */
	public function setApiVersion($api_version) {
		$this->api_version = $api_version;
	}


	/**
	 * @return string
	 */
	public function getOrganisation() {
		return $this->organisation;
	}


	/**
	 * @param string $organisation
	 */
	public function setOrganisation($organisation) {
		$this->organisation = $organisation;
	}
}