<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
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

require_once('./Services/Repository/classes/class.ilObjectPluginAccess.php');
require_once('class.ilObjOpenCast.php');

/**
 * Access/Condition checking for OpenCast object
 *
 * @author        Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version       1.0.00
 */
class ilObjOpenCastAccess extends ilObjectPluginAccess {

	const TXT_PERMISSION_DENIED = 'permission_denied';
	/**
	 * @var array
	 */
	protected static $cache = array();


	/**
	 * @param string $a_cmd
	 * @param string $a_permission
	 * @param int    $a_ref_id
	 * @param int    $a_obj_id
	 * @param string $a_user_id
	 *
	 * @return bool
	 */
	function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id = NULL, $a_user_id = '') {
		global $ilUser, $ilAccess;
		/**
		 * @var $ilAccess ilAccessHandler
		 */
		if ($a_user_id == '') {
			$a_user_id = $ilUser->getId();
		}
		if ($a_obj_id === NULL) {
			$a_obj_id = ilObject2::_lookupObjId($a_ref_id);
		}
		switch ($a_permission) {
			case 'read':
				if (! ilObjOpenCastAccess::checkOnline($a_obj_id) AND ! $ilAccess->checkAccessOfUser($a_user_id, 'write', '', $a_ref_id)
				) {
					return true;
				}
				break;
		}

		return true;
	}


	protected static function redirectNonAccess() {
		global $ilCtrl;
		ilUtil::sendFailure(ilOpenCastPlugin::getInstance()->txt(self::TXT_PERMISSION_DENIED), true);
		$ilCtrl->redirectByClass('ilRepositoryGUI');
	}


	/**
	 * @param $a_id
	 *
	 * @return bool
	 */
	static function checkOnline($a_id) {
		require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/class.xoctOpenCast.php');
		/**
		 * @var $xoctOpenCast xoctOpenCast
		 */
		$xoctOpenCast = xoctOpenCast::findOrGetInstance($a_id);

		return $xoctOpenCast->isObjOnline();
	}


	/**
	 * @param $ref_id
	 *
	 * @return bool
	 */
	public static function hasWriteAccess($ref_id = NULL) {
		if ($ref_id === NULL) {
			$ref_id = $_GET['ref_id'];
		}

		return self::_checkAccess('write', 'write', $ref_id);
	}


	//	/**
	//	 * @param bool $redirect
	//	 *
	//	 * @return bool
	//	 */
	//	public static function isGlobalAdmin($redirect = false) {
	//		global $rbacreview, $ilUser;
	//		/**
	//		 * @var $rbacreview ilRbacReview
	//		 */
	//
	//		$isAssigned = $rbacreview->isAssigned($ilUser->getId(), 2);
	//
	//		if (! $isAssigned AND $redirect) {
	//			self::redirectNonAccess();
	//		}
	//
	//		return $isAssigned;
	//	}
	//
	//
	//	/**
	//	 * @param bool $redirect
	//	 *
	//	 * @return bool
	//	 */
	//	public static function isAdmin($redirect = false) {
	//		global $rbacreview, $ilUser;
	//		// TODO
	//		if (self::isGlobalAdmin()) {
	//			return true;
	//		}
	//		if ($redirect) {
	//			self::redirectNonAccess();
	//		}
	//
	//		return false;
	//	}
	//
	//
	//	/**
	//	 * @param bool $redirect
	//	 *
	//	 * @return bool
	//	 */
	//	public static function isManager($redirect = false) {
	//		global $rbacreview, $ilUser;
	//		// TODO
	//
	//		if ($redirect) {
	//			self::redirectNonAccess();
	//		}
	//
	//		return false;
	//	}

}

?>
