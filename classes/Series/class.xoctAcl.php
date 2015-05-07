<?php

/**
 * Class xoctAcl
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctAcl {

	/**
	 *
	 */
	public function __construct() {
	}


	/**
	 * @var bool
	 */
	public $allow;
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