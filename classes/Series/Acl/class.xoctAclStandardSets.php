<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/Acl/class.xoctAcl.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/class.xoctConf.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/IVTGroup/class.xoctUser.php');

/**
 * Class xoctAclStandardSets
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctAclStandardSets {

	/**
	 * @var array
	 */
	protected $acls = array();


	/**
	 * xoctAclStandardSets constructor.
	 * @param xoctUser|null $user
	 * @param xoctUser|null $ivt_owner
	 */
	public function __construct($role_names = array()) {
		$acls = array();
		// standard roles
		foreach (xoctConf::get(xoctConf::F_STD_ROLES) as $std_role) {
			$acl = new xoctAcl();
			$acl->setRole($std_role);
			$acl->setAllow(true);
			$acl->setAction(xoctAcl::READ);
			$acls[] = $acl;

			$acl = new xoctAcl();
			$acl->setRole($std_role);
			$acl->setAllow(true);
			$acl->setAction(xoctAcl::WRITE);
			$acls[] = $acl;
		}

		// User Specific
		foreach ($role_names as $role) {
			$acl = new xoctAcl();
			$acl->setRole($role);
			$acl->setAllow(true);
			$acl->setAction(xoctAcl::WRITE);
			$acls[] = $acl;

			$acl = new xoctAcl();
			$acl->setRole($role);
			$acl->setAllow(true);
			$acl->setAction(xoctAcl::READ);
			$acls[] = $acl;
		}

		$this->setAcls($acls);
	}


	/**
	 * @return array
	 */
	public function getAcls() {
		return $this->acls;
	}


	/**
	 * @param array $acls
	 */
	public function setAcls($acls) {
		$this->acls = $acls;
	}
}

?>
