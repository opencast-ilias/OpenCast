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
	 * @var xoctAcl[]
	 */
	protected $series = array();
	/**
	 * @var xoctAcl[]
	 */
	protected $event = array();


	/**
	 * @param ilObjuser $ilUser
	 */
	public function __construct(ilObjuser $ilUser) {
		// PRODUCER
		$xoctAcl = new xoctAcl();
		$xoctAcl->setRole(xoctConf::get(xoctConf::F_ROLE_PRODUCER));
		$xoctAcl->setAction(xoctAcl::WRITE);
		$xoctAcl->setAllow(true);

		$this->series[] = $xoctAcl;
		$this->event[] = $xoctAcl;

		// PRODUCER
		$xoctAcl = new xoctAcl();
		$xoctAcl->setRole(xoctConf::get(xoctConf::F_ROLE_PRODUCER));
		$xoctAcl->setAction(xoctAcl::READ);
		$xoctAcl->setAllow(true);

		$this->series[] = $xoctAcl;
		$this->event[] = $xoctAcl;

		// EXT APPLICATION
		$xoctAcl = new xoctAcl();
		$xoctAcl->setRole(xoctConf::get(xoctConf::F_ROLE_EXT_APPLICATION));
		$xoctAcl->setAction(xoctAcl::WRITE);
		$xoctAcl->setAllow(true);

		$this->series[] = $xoctAcl;
		$this->event[] = $xoctAcl;

		// EXT APPLICATION
		$xoctAcl = new xoctAcl();
		$xoctAcl->setRole(xoctConf::get(xoctConf::F_ROLE_EXT_APPLICATION));
		$xoctAcl->setAction(xoctAcl::READ);
		$xoctAcl->setAllow(true);

		$this->series[] = $xoctAcl;
		$this->event[] = $xoctAcl;

		// F_ROLE_FEDERATION_MEMBER
		$xoctAcl = new xoctAcl();
		$xoctAcl->setRole(xoctConf::get(xoctConf::F_ROLE_FEDERATION_MEMBER));
		$xoctAcl->setAction(xoctAcl::WRITE);
		$xoctAcl->setAllow(true);

		$this->series[] = $xoctAcl;
		$this->event[] = $xoctAcl;

		// F_ROLE_FEDERATION_MEMBER
		$xoctAcl = new xoctAcl();
		$xoctAcl->setRole(xoctConf::get(xoctConf::F_ROLE_FEDERATION_MEMBER));
		$xoctAcl->setAction(xoctAcl::READ);
		$xoctAcl->setAllow(true);

		$this->series[] = $xoctAcl;
		$this->event[] = $xoctAcl;

		// F_ROLE_ROLE_EXTERNAL_APPLICATION_MEMBER
		$xoctAcl = new xoctAcl();
		$xoctAcl->setRole(xoctConf::get(xoctConf::F_ROLE_ROLE_EXTERNAL_APPLICATION_MEMBER));
		$xoctAcl->setAction(xoctAcl::WRITE);
		$xoctAcl->setAllow(true);

		$this->series[] = $xoctAcl;
		$this->event[] = $xoctAcl;

		// F_ROLE_ROLE_EXTERNAL_APPLICATION_MEMBER
		$xoctAcl = new xoctAcl();
		$xoctAcl->setRole(xoctConf::get(xoctConf::F_ROLE_ROLE_EXTERNAL_APPLICATION_MEMBER));
		$xoctAcl->setAction(xoctAcl::READ);
		$xoctAcl->setAllow(true);

		$this->series[] = $xoctAcl;
		$this->event[] = $xoctAcl;
		$xoctUser = xoctUser::getInstance($ilUser);
		foreach ($xoctUser->getStandardAcls() as $acl) {
			$this->series[] = $acl;
			$this->event[] = $acl;
		}
	}


	/**
	 * @return xoctAcl[]
	 */
	public function getSeries() {
		return $this->series;
	}


	/**
	 * @param xoctAcl[] $series
	 */
	public function setSeries($series) {
		$this->series = $series;
	}


	/**
	 * @return xoctAcl[]
	 */
	public function getEvent() {
		return $this->event;
	}


	/**
	 * @param xoctAcl[] $event
	 */
	public function setEvent($event) {
		$this->event = $event;
	}
}

?>
