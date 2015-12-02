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


	/**
	 * @return bool
	 */
	public function isIVTAcl() {
		switch (xoctUser::getUserMapping()) {
			case xoctUser::MAP_EMAIL:
				return (strpos($this->getRole(), xoctConf::get(xoctConf::F_ROLE_USER_IVT_EMAIL_PREFIX)) === 0);
				break;
			case xoctUser::MAP_EXT_ID:
				return (strpos($this->getRole(), xoctConf::get(xoctConf::F_ROLE_USER_IVT_EXTERNAL_PREFIX)) === 0);
				break;
		}

		return false;
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
		return array(
			self::userRead()->__toStdClass(),
			self::adminWrite()->__toStdClass(),
			self::adminRead()->__toStdClass()
		);
	}


	/**
	 * @return xoctAcl[]
	 */
	public static function getStandardSetForEvent() {
		$acls = array();
		$acl = new xoctAcl();
		$acl->setRole(xoctConf::get(xoctConf::F_ROLE_EXT_APPLICATION));
		$acl->setAllow(true);
		$acl->setAction(xoctAcl::READ);
		$acls[] = $acl;

		$acl = new xoctAcl();
		$acl->setRole(xoctConf::get(xoctConf::F_ROLE_EXT_APPLICATION));
		$acl->setAllow(true);
		$acl->setAction(xoctAcl::WRITE);
		$acls[] = $acl;

		return $acls;
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