<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2009 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/
require_once __DIR__ . '/../vendor/autoload.php';
/**
 * Class ilObjOpenCast
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version 1.0.00
 */
class ilObjOpenCast extends ilObjectPlugin {

	/**
	 * @var bool
	 */
	protected $object;
	const DEV = false;


	/**
	 * @param int $a_ref_id
	 */
	public function __construct($a_ref_id = 0) {
		/**
		 * @var $ilDB ilDB
		 */
		global $DIC;
		$ilDB = $DIC['ilDB'];

		parent::__construct($a_ref_id);
		$this->db = $ilDB;
	}


	final function initType() {
		$this->setType(ilOpenCastPlugin::PLUGIN_ID);
	}


	public function doCreate() {
	}


	public function doRead() {
		xoctConf::setApiSettings();
		/**
		 * @var $xoctOpenCast xoctOpenCast
		 */
		$xoctOpenCast = xoctOpenCast::find($this->getId());

		// catch exception: the series may already be deleted in opencast (404 exception)
		try {
			$series = $xoctOpenCast->getSeries();
		} catch (xoctException $e) {
			ilUtil::sendInfo($e->getMessage(), true);
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
		global $DIC;
		$tree = $DIC['tree'];
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
			$ref_id = $tree->getParentId($ref_id);
		}

		$crs_or_grp_object[$ref_id] = ilObjectFactory::getInstanceByRefId($ref_id);
		return $crs_or_grp_object[$ref_id];
	}
}

?>
