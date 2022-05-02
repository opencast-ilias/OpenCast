<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/vendor/srag/dic/src/DICTrait.php');
use srag\DIC\OpenCast\DICTrait;
use srag\Plugins\Opencast\DI\OpencastDIC;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGroup;

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

	public function updateObjectFromSeries(Metadata $metadata)
	{
		PluginConfig::setApiSettings();
		if (self::dic()->ctrl()->isAsynch()) {
			// don't update title/description on async calls
			return;
		}

		$title = $metadata->getField(MDFieldDefinition::F_TITLE)->getValue();
		$description = $metadata->getField(MDFieldDefinition::F_DESCRIPTION)->getValue();
		if ($title != $this->getTitle() || $description != $this->getDescription()) {
			$this->setTitle($title);
			$this->setDescription($description);
			$this->update();
		}
	}


	public function doUpdate() {
	}


	public function doDelete() {
		$opencast_dic = OpencastDIC::getInstance();
		/** @var ObjectSettings $objectSettings */
		$objectSettings = ObjectSettings::find($this->getId());
		if ($objectSettings) {
			$opencast_dic->paella_config_storage_service()->delete($objectSettings->getPaellaPlayerFileId());
			$opencast_dic->paella_config_storage_service()->delete($objectSettings->getPaellaPlayerLiveFileId());
			$objectSettings->delete();
		}
		foreach (PermissionGroup::where(array('serie_id' => $this->getId()))->get() as $ivt_group) {
			$ivt_group->delete();
		}
	}


	/**
	 * @param ilObjOpenCast $new_obj
	 * @param               $a_target_id
	 * @param null $a_copy_id
	 *
	 * @return bool|void
	 */
	protected function doCloneObject($new_obj, $a_target_id, $a_copy_id = NULL) {
		PluginConfig::setApiSettings();
		/**
		 * @var $objectSettingsNew ObjectSettings
		 * @var $objectSettingsOld ObjectSettings
		 */
		$objectSettingsNew = new ObjectSettings();
		$objectSettingsNew->setObjId($new_obj->getId());
		$objectSettingsOld = ObjectSettings::find($this->getId());

		$objectSettingsNew->setSeriesIdentifier($objectSettingsOld->getSeriesIdentifier());
		$objectSettingsNew->setIntroductionText($objectSettingsOld->getIntroductionText());
		$objectSettingsNew->setAgreementAccepted($objectSettingsOld->getAgreementAccepted());
		$objectSettingsNew->setOnline(false);
		$objectSettingsNew->setPermissionAllowSetOwn($objectSettingsOld->getPermissionAllowSetOwn());
		$objectSettingsNew->setStreamingOnly($objectSettingsOld->getStreamingOnly());
		$objectSettingsNew->setUseAnnotations($objectSettingsOld->getUseAnnotations());
		$objectSettingsNew->setPermissionPerClip($objectSettingsOld->getPermissionPerClip());

		$objectSettingsNew->create();
	}

	public function getParentCourseOrGroup() {
		return self::_getParentCourseOrGroup($this->ref_id);
	}

	/**
	 * TODO: weird static method - think about where this belongs
	 *
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
			$ref_id = self::dic()->repositoryTree()->getParentId($ref_id);
		}

		$crs_or_grp_object[$ref_id] = ilObjectFactory::getInstanceByRefId($ref_id);
		return $crs_or_grp_object[$ref_id];
	}

    /**
     * @return string
     */
    public static function _getCourseOrGroupRole() {
		$crs_or_group = self::_getParentCourseOrGroup($_GET['ref_id']);

		if (self::dic()->rbac()->review()->isAssigned(self::dic()->user()->getId(), $crs_or_group->getDefaultAdminRole())) {
			return $crs_or_group instanceof ilObjCourse ? 'Kursadministrator' : 'Gruppenadministrator';
        }
        if (self::dic()->rbac()->review()->isAssigned(self::dic()->user()->getId(), $crs_or_group->getDefaultMemberRole())) {
			return $crs_or_group instanceof ilObjCourse ? 'Kursmitglied' : 'Gruppenmitglied';
        }
        if (($crs_or_group instanceof ilObjCourse) && self::dic()->rbac()->review()->isAssigned(self::dic()->user()->getId(), $crs_or_group->getDefaultTutorRole())) {
			return 'Kurstutor';
        }

        return 'Unbekannt';
   	}
}

?>
