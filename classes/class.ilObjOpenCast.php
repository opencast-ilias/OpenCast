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

require_once('./Services/Repository/classes/class.ilObjectPlugin.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/class.xoctConf.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Request/class.xoctCurlSettings.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Request/class.xoctCurl.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctCache.php');

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


	/**
	 * @param int $a_ref_id
	 */
	public function __construct($a_ref_id = 0) {
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		parent::__construct($a_ref_id);
		$this->db = $ilDB;
	}


	final function initType() {
		$this->setType(ilOpenCastPlugin::XOCT);
	}


	public function doCreate() {
	}


	public function doRead() {
		$xoctCurlSettings = new xoctCurlSettings();
		$xoctCurlSettings->setUsername(xoctConf::get(xoctConf::F_CURL_USERNAME));
		$xoctCurlSettings->setPassword(xoctConf::get(xoctConf::F_CURL_PASSWORD));
//		$xoctCurlSettings->setVerifyPeer(false);
//		$xoctCurlSettings->set(false);
		$xoctCurlSettings->setDebugLevel(xoctConf::get(xoctConf::F_CURL_DEBUG_LEVEL));
		xoctCurl::init($xoctCurlSettings);
		xoctCache::setOverrideActive(true);
	}


	public function doUpdate() {
	}


	public function doDelete() {
	}


	/**
	 * @param ilObjOpenCast $new_obj
	 * @param               $a_target_id
	 * @param null          $a_copy_id
	 *
	 * @return bool|void
	 */
	protected function doCloneObject(ilObjOpenCast $new_obj, $a_target_id, $a_copy_id = NULL) {
	}


	/**
	 * @param $ref_id
	 *
	 * @return int
	 */
	public static function returnParentCrsRefId($ref_id) {
		global $tree;
		/**
		 * @var $tree ilTree
		 */
		while (ilObject2::_lookupType($ref_id, true) != 'crs') {
			if ($ref_id == 1) {
				ilUtil::sendFailure('OpenCast-Objects can be created in courses only.', true);
				ilUtil::redirect('/');
			}
			$ref_id = $tree->getParentId($ref_id);
		}

		return $ref_id;
	}
}

?>
