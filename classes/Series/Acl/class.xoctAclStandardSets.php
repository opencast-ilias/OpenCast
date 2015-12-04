<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/Acl/class.xoctAcl.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/class.xoctConf.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Group/class.xoctUser.php');

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
	public function __construct(xoctUser $user = null, xoctUser $ivt_owner = null) {
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

		$acl = new xoctAcl();
		$acl->setRole(xoctConf::get(xoctConf::F_ROLE_PRODUCER));
		$acl->setAllow(true);
		$acl->setAction(xoctAcl::WRITE);
		$acls[] = $acl;

		$acl = new xoctAcl();
		$acl->setRole(xoctConf::get(xoctConf::F_ROLE_PRODUCER));
		$acl->setAllow(true);
		$acl->setAction(xoctAcl::READ);
		$acls[] = $acl;

		// User Specific
		if ($user instanceof xoctUser) {

			$acl = new xoctAcl();
			$acl->setRole($user->getRoleName());
			$acl->setAllow(true);
			$acl->setAction(xoctAcl::WRITE);
			$acls[] = $acl;

			$acl = new xoctAcl();
			$acl->setRole($user->getRoleName());
			$acl->setAllow(true);
			$acl->setAction(xoctAcl::READ);
			$acls[] = $acl;
		}

		if ($ivt_owner instanceof xoctUser) {

			$acl = new xoctAcl();
			$acl->setRole($ivt_owner->getIVTRoleName());
			$acl->setAllow(true);
			$acl->setAction(xoctAcl::WRITE);
			$acls[] = $acl;

			$acl = new xoctAcl();
			$acl->setRole($ivt_owner->getIVTRoleName());
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
