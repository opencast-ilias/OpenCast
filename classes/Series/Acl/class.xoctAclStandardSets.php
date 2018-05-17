<?php
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
		foreach (xoctConf::getConfig(xoctConf::F_STD_ROLES) as $std_role) {
			if (!$std_role) {
				continue;
			}

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
