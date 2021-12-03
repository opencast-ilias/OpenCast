<?php

use srag\Plugins\Opencast\Model\ACL\ACL;

/**
 * Class xoctAclStandardSets
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctAclStandardSets {

	/**
	 * @var ACL
	 */
	protected $acl;


	/**
	 * xoctAclStandardSets constructor.
	 * @param array $role_names
	 */
	public function __construct(array $role_names = []) {
		$entries = [];
		// standard roles
		foreach (xoctConf::getConfig(xoctConf::F_STD_ROLES) as $std_role) {
			if (!$std_role) {
				continue;
			}
			$entries[] = new ACLEntry($std_role, ACLEntry::READ, true);
			$entries[] = new ACLEntry($std_role, ACLEntry::WRITE, true);
		}

		// User Specific
		foreach ($role_names as $role) {
			$entries[] = new ACLEntry($role, ACLEntry::WRITE, true);
			$entries[] = new ACLEntry($role, ACLEntry::READ, true);
		}

		$this->setAcl(new ACL($entries));
	}


	public function getAcl() : ACL {
		return $this->acl;
	}


	public function setAcl(ACL $acl) {
		$this->acl = $acl;
	}
}

?>
