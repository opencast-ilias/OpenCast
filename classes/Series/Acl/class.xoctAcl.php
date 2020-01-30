<?php

use srag\Plugins\Opencast\Model\API\APIObject;

/**
 * Class xoctAcl
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctAcl extends APIObject {

	const ADMIN = 'ROLE_ADMIN';
	const USER = 'ROLE_ADMIN';
	const WRITE = 'write';
	const READ = 'read';


	/**
	 * @return bool
	 */
	public function isIVTAcl() {
		switch (xoctUser::getUserMapping()) {
			case xoctUser::MAP_EMAIL:
				return (strpos($this->getRole(), xoctConf::getConfig(xoctConf::F_ROLE_OWNER_EMAIL_PREFIX)) === 0);
				break;
			case xoctUser::MAP_EXT_ID:
				return (strpos($this->getRole(), xoctConf::getConfig(xoctConf::F_ROLE_OWNER_EXTERNAL_PREFIX)) === 0);
				break;
		}

		return false;
	}


	/**
	 * @var bool
	 */
	public $allow = false;
	/**
	 * @var string
	 */
	public $action;
	/**
	 * @var string
	 */
	public $role;


	/**
	 * @return boolean
	 */
	public function isAllow() {
		return $this->allow;
	}


	/**
	 * @param boolean $allow
	 */
	public function setAllow($allow) {
		$this->allow = $allow;
	}


	/**
	 * @return string
	 */
	public function getAction() {
		return $this->action;
	}


	/**
	 * @param string $action
	 */
	public function setAction($action) {
		$this->action = $action;
	}


	/**
	 * @return string
	 */
	public function getRole() {
		return $this->role;
	}


	/**
	 * @param string $role
	 */
	public function setRole($role) {
		$this->role = $role;
	}
}