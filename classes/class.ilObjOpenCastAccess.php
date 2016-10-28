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
require_once('./Modules/Course/classes/class.ilCourseParticipants.php');
require_once('class.ilObjOpenCast.php');

/**
 * Access/Condition checking for OpenCast object
 *
 * @author        Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version       1.0.00
 */
class ilObjOpenCastAccess extends ilObjectPluginAccess {

	const ROLE_MEMBER = 1;
	const ROLE_ADMIN = 2;
	const ROLE_TUTOR = 3;
	const TXT_PERMISSION_DENIED = 'permission_denied';

	/**
	 * @var array
	 */
	protected static $custom_rights = array(
		'upload',
		'edit_videos',
	);
	/**
	 * @var array
	 */
	protected static $cache = array();
	/**
	 * @var array
	 */
	protected static $members = array();
	/**
	 * @var array
	 */
	protected static $tutors = array();
	/**
	 * @var array
	 */
	protected static $admins = array();


	/**
	 * @param string $a_cmd
	 * @param string $a_permission
	 * @param int $a_ref_id
	 * @param int $a_obj_id
	 * @param string $a_user_id
	 *
	 * @return bool
	 */
	public function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id = NULL, $a_user_id = '') {
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
				if (!ilObjOpenCastAccess::checkOnline($a_obj_id) AND !$ilAccess->checkAccessOfUser($a_user_id, 'write', '', $a_ref_id)) {
					return false;
				}
				break;
			case 'visible':
				if (!ilObjOpenCastAccess::checkOnline($a_obj_id) AND !$ilAccess->checkAccessOfUser($a_user_id, 'write', '', $a_ref_id)) {
					return false;
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

		return (bool)$xoctOpenCast->isObjOnline();
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
		global $ilAccess;

		/**
		 * @var $ilAccess ilAccesshandler
		 */

		return $ilAccess->checkAccess('write', '', $ref_id);
	}

	public static function checkAction($cmd, xoctEvent $xoctEvent = NULL, xoctUser $xoctUser = NULL, $ref_id = NULL) {
		if ($xoctUser === NULL) {
			global $ilUser;
			$xoctUser = xoctUser::getInstance($ilUser);
		}

		switch ($cmd) {
			case 'edit_owner':
				return
					self::hasPermission('edit_videos', $ref_id)
					&& $xoctEvent->getProcessingState() != xoctEvent::STATE_ENCODING;
			case 'share_event':
				return
					self::hasPermission('edit_videos', $ref_id)
					&& $xoctEvent->isOwner($xoctUser)
					&& $xoctEvent->getProcessingState() != xoctEvent::STATE_ENCODING
					&& $xoctEvent->getProcessingState() != xoctEvent::STATE_FAILED;
			case 'cut':
				return
					self::hasPermission('edit_videos', $ref_id)
					&& $xoctEvent->getProcessingState() != xoctEvent::STATE_FAILED;
			case 'delete_event':
				return
					(self::hasPermission('edit_videos') || (self::hasPermission('upload') && $xoctEvent->isOwner($xoctUser)))
					&& $xoctEvent->getProcessingState() != xoctEvent::STATE_ENCODING;
			case 'edit_event':
			case 'set_online_offline':
				return
					self::hasPermission('edit_videos')
					&& $xoctEvent->getProcessingState() != xoctEvent::STATE_ENCODING
					&& $xoctEvent->getProcessingState() != xoctEvent::STATE_FAILED;
			case 'add_event':
				return
					self::hasPermission('upload')
					|| self::hasPermission('edit_videos');

		}
	}


	/**
	 * @param      $right
	 * @param null $ref_id
	 *
	 * @return bool
	 */
	public static function hasPermission($right, $ref_id = NULL) {
		if ($ref_id === NULL) {
			$ref_id = $_GET['ref_id'];
		}
		global $ilAccess;

		$prefix = in_array($right, self::$custom_rights) ? "rep_robj_xoct_" : "";
		/**
		 * @var $ilAccess ilAccesshandler
		 */
		return $ilAccess->checkAccess($prefix.$right, '', $ref_id);
	}

	/**
	 * @return int
	 */
	public static function getCourseRole() {
		static $role;
		if ($role) {
			return $role;
		}
		global $ilUser;
		self::initRoleMembers();
		switch (true) {
			case in_array($ilUser->getId(), self::$admins):
				$role = self::ROLE_ADMIN;
				break;
			case in_array($ilUser->getId(), self::$members):
				$role = self::ROLE_MEMBER;
				break;
			case in_array($ilUser->getId(), self::$tutors):
				$role = self::ROLE_ADMIN;
				break;
		}

		return $role;
	}


	protected static function initRoleMembers() {
		static $init;
		if ($init) {
			return true;
		}

		$cp = new ilCourseParticipants(self::getCourseId());
		self::setAdmins($cp->getAdmins());
		self::setTutors($cp->getTutors());
		self::setMembers($cp->getMembers());
		$init = true;
	}


	/**
	 * @return int
	 */
	public static function getCourseId() {
		static $obj_id;
		if ($obj_id) {
			return $obj_id;
		}
		global $tree;
		/**
		 * @var $tree ilTree
		 */
		foreach ($tree->getNodePath($_GET['ref_id']) as $node) {
			if ($node['type'] == 'crs') {
				$obj_id = $node['obj_id'];
			}
		}

		return $obj_id;
	}


	/**
	 * @return array
	 */
	public static function getMembers() {
		self::initRoleMembers();

		return self::$members;
	}


	/**
	 * @param array $members
	 */
	public static function setMembers($members) {
		self::$members = $members;
	}


	/**
	 * @return array
	 */
	public static function getTutors() {
		self::initRoleMembers();

		return self::$tutors;
	}


	/**
	 * @param array $tutors
	 */
	public static function setTutors($tutors) {

		self::$tutors = $tutors;
	}


	/**
	 * @return array
	 */
	public static function getAdmins() {
		self::initRoleMembers();

		return self::$admins;
	}


	/**
	 * @param array $admins
	 */
	public static function setAdmins($admins) {
		self::$admins = $admins;
	}
}

?>
