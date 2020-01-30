<?php

use srag\Plugins\Opencast\Model\API\APIObject;

/**
 * Class xoctProperties
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctProperties extends APIObject {

	/**
	 * @var int
	 */
	protected $theme = 0;
	/**
	 * @var bool
	 */
	protected $ondemand = true;
	/**
	 * @var bool
	 */
	protected $live = false;


	/**
	 * @return int
	 */
	public function getTheme() {
		return $this->theme;
	}


	/**
	 * @param int $theme
	 */
	public function setTheme($theme) {
		$this->theme = $theme;
	}


	/**
	 * @return boolean
	 */
	public function isOndemand() {
		return $this->ondemand;
	}


	/**
	 * @param boolean $ondemand
	 */
	public function setOndemand($ondemand) {
		$this->ondemand = $ondemand;
	}


	/**
	 * @return boolean
	 */
	public function isLive() {
		return $this->live;
	}


	/**
	 * @param boolean $live
	 */
	public function setLive($live) {
		$this->live = $live;
	}
}