<?php
use srag\DIC\OpenCast\DICTrait;
/**
 * Class ilObjOpenCast
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version 1.0.00
 */
class ilObjOpenCast extends ilObjectPlugin {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	/**
	 * @var bool
	 */
	protected $object;
	const DEV = false;


	/**
	 * @param int $a_ref_id
	 */
	public function __construct($a_ref_id = 0) {
		parent::__construct($a_ref_id);
	}


	final function initType() {
		$this->setType(ilOpenCastPlugin::PLUGIN_ID);
	}


	public function doCreate() {
	}

	/**
	 * @throws xoctException
	 */
	public function updateObjectFromSeries()
	{
		xoctConf::setApiSettings();
		/**
		 * @var $xoctOpenCast xoctOpenCast
		 */
		$xoctOpenCast = xoctOpenCast::find($this->getId());
		if (self::dic()->ctrl()->isAsynch()) {
			// don't update title/description on async calls
			return;
		}

		// catch exception: the series may already be deleted in opencast (404 exception)
		try {
			$series = $xoctOpenCast->getSeries();
		} catch (xoctException $e) {
			if (ilContext::hasHTML()) {
				ilUtil::sendInfo($e->getMessage(), true);
			} else {
				// if the exception is thrown during a cron job e.g., we want the exception to be thrown
				throw $e;
			}
			return;
		}

		if ($series->getTitle() != $this->getTitle() || $series->getDescription() != $this->getDescription()) {
			$this->setTitle($series->getTitle());
			$this->setDescription($series->getDescription());
			$this->update();
		}
	}


	public function doUpdate() {
	}


	public function doDelete() {
		xoctOpenCast::find($this->getId())->delete();
	}


	/**
	 * @param ilObjOpenCast $new_obj
	 * @param               $a_target_id
	 * @param null $a_copy_id
	 *
	 * @return bool|void
	 */
	protected function doCloneObject($new_obj, $a_target_id, $a_copy_id = NULL) {
		xoctConf::setApiSettings();
		/**
		 * @var $xoctOpenCastNew xoctOpenCast
		 * @var $xoctOpenCastOld xoctOpenCast
		 */
		require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/class.xoctOpenCast.php');
		$xoctOpenCastNew = new xoctOpenCast();
		$xoctOpenCastNew->setObjId($new_obj->getId());
		$xoctOpenCastOld = xoctOpenCast::find($this->getId());

		$xoctOpenCastNew->setSeriesIdentifier($xoctOpenCastOld->getSeriesIdentifier());
		$xoctOpenCastNew->setIntroductionText($xoctOpenCastOld->getIntroductionText());
		$xoctOpenCastNew->setAgreementAccepted($xoctOpenCastOld->getAgreementAccepted());
		$xoctOpenCastNew->setOnline(false);
		$xoctOpenCastNew->setPermissionAllowSetOwn($xoctOpenCastOld->getPermissionAllowSetOwn());
		$xoctOpenCastNew->setStreamingOnly($xoctOpenCastOld->getStreamingOnly());
		$xoctOpenCastNew->setUseAnnotations($xoctOpenCastOld->getUseAnnotations());
		$xoctOpenCastNew->setPermissionPerClip($xoctOpenCastOld->getPermissionPerClip());

		$xoctOpenCastNew->create();
	}

	public function getParentCourseOrGroup() {
		return self::_getParentCourseOrGroup($this->ref_id);
	}

	/**
	 * @param $ref_id
	 *
	 * @return bool|ilObjCourse|ilObjGroup
	 */
	public static function _getParentCourseOrGroup($ref_id) {
		static $crs_or_grp_object;
		if (!is_array($crs_or_grp_object)) {
			$crs_or_grp_object = array();
		}

		if (isset($crs_or_grp_object[$ref_id])) {
			return $crs_or_grp_object[$ref_id];
		}

		/**
		 * @var $tree ilTree
		 */
		while (!in_array(ilObject2::_lookupType($ref_id, true), array('crs', 'grp'))) {
			if ($ref_id == 1) {
				$crs_or_grp_object[$ref_id] = false;
				return $crs_or_grp_object[$ref_id];
			}
			$ref_id = self::dic()->tree()->getParentId($ref_id);
		}

		$crs_or_grp_object[$ref_id] = ilObjectFactory::getInstanceByRefId($ref_id);
		return $crs_or_grp_object[$ref_id];
	}

    /**
     * @return string
     */
    public static function _getCourseOrGroupRole() {
		$crs_or_group = self::_getParentCourseOrGroup($_GET['ref_id']);

		if (self::dic()->rbacreview()->isAssigned(self::dic()->user()->getId(), $crs_or_group->getDefaultAdminRole())) {
			return $crs_or_group instanceof ilObjCourse ? 'Kursadministrator' : 'Gruppenadministrator';
        }
        if (self::dic()->rbacreview()->isAssigned(self::dic()->user()->getId(), $crs_or_group->getDefaultMemberRole())) {
			return $crs_or_group instanceof ilObjCourse ? 'Kursmitglied' : 'Gruppenmitglied';
        }
        if (($crs_or_group instanceof ilObjCourse) && self::dic()->rbacreview()->isAssigned(self::dic()->user()->getId(), $crs_or_group->getDefaultTutorRole())) {
			return 'Kurstutor';
        }

        return 'Unbekannt';
   	}
}

?>
