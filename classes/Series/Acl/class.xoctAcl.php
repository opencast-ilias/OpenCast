<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Object/class.xoctObject.php');

/**
 * Class xoctAcl
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctAcl extends xoctObject {

	const ADMIN = 'ROLE_ADMIN';
	const USER = 'ROLE_ADMIN';
	const WRITE = 'write';
	const READ = 'read';


	public function read() {
		// TODO: Implement read() method.
	}


	public function update() {
		// TODO: Implement update() method.
	}


	public function create() {
		// TODO: Implement create() method.
	}


	public function delete() {
		// TODO: Implement delete() method.
	}


	/**
	 * @return xoctAcl
	 */
	public static function userRead() {
		$obj = new self();
		$obj->setAction(self::READ);
		$obj->setRole(self::USER);
		$obj->setAllow(true);

		return $obj;
	}


	/**
	 * @return xoctAcl
	 */
	public static function adminWrite() {
		$obj = new self();
		$obj->setAction(self::WRITE);
		$obj->setRole(self::ADMIN);
		$obj->setAllow(true);

		return $obj;
	}


	/**
	 * @return xoctAcl
	 */
	public static function adminRead() {
		$obj = new self();
		$obj->setAction(self::READ);
		$obj->setRole(self::ADMIN);
		$obj->setAllow(true);

		return $obj;
	}


	/**
	 * @return stdClass[]
	 */
	public static function getStandardSet() {
		return array( self::userRead()->__toStdClass(), self::adminWrite()->__toStdClass(), self::adminRead()->__toStdClass() );
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