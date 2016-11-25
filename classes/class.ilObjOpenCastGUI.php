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
require_once('./Services/Repository/classes/class.ilObjectPluginGUI.php');
require_once('class.ilOpenCastPlugin.php');
require_once('./Services/Link/classes/class.ilLink.php');
require_once('./Services/InfoScreen/classes/class.ilInfoScreenGUI.php');
require_once('class.ilObjOpenCast.php');
require_once('./Services/InfoScreen/classes/class.ilInfoScreenGUI.php');
require_once('./Services/Repository/classes/class.ilRepUtilGUI.php');
require_once('./Services/AccessControl/classes/class.ilPermissionGUI.php');

require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/class.xoctSeriesGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/class.xoctOpenCast.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Event/class.xoctEventGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/Acl/class.xoctAclStandardSets.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/IVTGroup/class.xoctIVTGroupGUI.php');

/**
 * User Interface class for example repository object.
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version           1.0.00
 *
 * Integration into control structure:
 * - The GUI class is called by ilRepositoryGUI
 * - GUI classes used by this class are ilPermissionGUI (provides the rbac
 *   screens) and ilInfoScreenGUI (handles the info screen).
 *
 * @ilCtrl_isCalledBy ilObjOpenCastGUI: ilRepositoryGUI, ilObjPluginDispatchGUI, ilAdministrationGUI
 * @ilCtrl_Calls      ilObjOpenCastGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI
 *
 */
class ilObjOpenCastGUI extends ilObjectPluginGUI {

	const CMD_SHOW_CONTENT = 'showContent';
	const CMD_REDIRECT_SETTING = 'redirectSettings';
	const TAB_EVENTS = 'series';
	const TAB_SETTINGS = 'settings';
	const TAB_INFO = 'info_short';
	const TAB_GROUPS = 'groups';
	/**
	 * @var ilObjOpenCast
	 */
	//	public $object;
	/**
	 * @var ilOpenCastPlugin
	 */
	protected $pl;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilPropertyFormGUI
	 */
	protected $form;
	/**
	 * @var ilNavigationHistory
	 */
	protected $history;
	/**
	 * @var ilTabsGUI
	 */
	public $tabs_gui;
	/**
	 * @var ilAccessHandler
	 */
	protected $access;


	protected function afterConstructor() {
		global $tpl, $ilCtrl, $ilAccess, $ilNavigationHistory, $ilTabs;
		/**
		 * @var $tpl                 ilTemplate
		 * @var $ilCtrl              ilCtrl
		 * @var $ilAccess            ilAccessHandler
		 * @var $ilNavigationHistory ilNavigationHistory
		 */
		$this->tpl = $tpl;
		$this->history = $ilNavigationHistory;
		$this->access = $ilAccess;
		$this->ctrl = $ilCtrl;
		$this->tabs_gui = $ilTabs;
		$this->pl = ilOpenCastPlugin::getInstance();
	}


	/**
	 * @return string
	 */
	final function getType() {
		return ilOpenCastPlugin::XOCT;
	}


	/**
	 * @param $cmd
	 */
	public function performCommand($cmd) {
		$this->{$cmd}();
	}


	public function executeCommand() {
		$this->checkPermission('read');
		try {
			xoctConf::setApiSettings();
			$next_class = $this->ctrl->getNextClass();
			$this->tpl->getStandardTemplate();

			switch ($next_class) {
				case 'xoctseriesgui':
				case 'xocteventgui':
				case 'xoctivtgroupgui':
				case 'xoctivtgroupparticipantgui':
				case 'xoctinvitationgui':
					$xoctOpenCast = $this->initHeader();
					$this->setTabs();
					$xoctSeriesGUI = new $next_class($xoctOpenCast);
					$this->ctrl->forwardCommand($xoctSeriesGUI);
					$this->tpl->show();
					break;
				case 'ilpermissiongui':
					$this->initHeader(false);
					parent::executeCommand();
					break;
				default:
					parent::executeCommand();
					break;
			}
		} catch (xoctException $e) {
			ilUtil::sendFailure($e->getMessage());
			$this->tpl->show();
		}
	}


	protected function showContent() {
		$this->ctrl->redirect(new xoctEventGUI());
	}


	protected function redirectSettings() {
		$this->ctrl->redirect(new xoctSeriesGUI(), xoctSeriesGUI::CMD_EDIT);
	}


	/**
	 * @return string
	 */
	public function getAfterCreationCmd() {
		return self::CMD_SHOW_CONTENT;
	}


	/**
	 * @return string
	 */
	function getStandardCmd() {
		return self::CMD_SHOW_CONTENT;
	}


	/**
	 * @return bool
	 */
	protected function setTabs() {
		global $lng, $ilUser, $tree;

		/**
		 * @var $xoctOpenCast xoctOpenCast
		 */
		$xoctOpenCast = xoctOpenCast::find($this->obj_id);
		if (!$xoctOpenCast instanceof xoctOpenCast) {
			return false;
		}

		$this->tabs_gui->addTab(self::TAB_EVENTS, $this->pl->txt('tab_event_index'), $this->ctrl->getLinkTarget(new xoctEventGUI(), xoctEventGUI::CMD_STANDARD));
		$this->tabs_gui->addTab(self::TAB_INFO, $this->pl->txt('tab_info'), $this->ctrl->getLinkTarget($this, 'infoScreen'));

		if (ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_EDIT_SETTINGS)) {
			$this->tabs_gui->addTab(self::TAB_SETTINGS, $this->pl->txt('tab_series_settings'), $this->ctrl->getLinkTarget(new xoctSeriesGUI(), xoctSeriesGUI::CMD_EDIT));
		}

		if ($xoctOpenCast->getPermissionPerClip()
			&& ilObjOpenCastAccess::checkAction(ilObjOpenCastAccess::ACTION_MANAGE_IVT_GROUPS)
			&& ($tree->checkForParentType($this->ref_id, 'crs') || $tree->checkForParentType($this->ref_id, 'grp'))) {
			$this->tabs_gui->addTab(self::TAB_GROUPS, $this->pl->txt('tab_groups'), $this->ctrl->getLinkTarget(new xoctIVTGroupGUI()));
		}
		if ($ilUser->getId() == 6 AND ilObjOpenCast::DEV) {
			$this->tabs_gui->addTab('migrate_event', $this->pl->txt('tab_migrate_event'), $this->ctrl->getLinkTarget(new xoctEventGUI(), 'search'));
			$this->tabs_gui->addTab('list_all', $this->pl->txt('tab_list_all'), $this->ctrl->getLinkTarget(new xoctEventGUI(), 'listAll'));
		}

		if ($this->checkPermissionBool("edit_permission")) {
			$this->tabs_gui->addTab("perm_settings", $lng->txt("perm_settings"), $this->ctrl->getLinkTargetByClass(array(
				get_class($this),
				"ilpermissiongui",
			), "perm"));
		}

		return true;
	}


	/**
	 * @param string $a_new_type
	 *
	 * @return array
	 */
	protected function initCreationForms($a_new_type) {
		if (!ilObjOpenCast::getParentCourseOrGroup($_GET['ref_id'])) {
			ilUtil::sendFailure($this->pl->txt('msg_creation_failed'), true);
			ilUtil::redirect('/');
		}
		$this->ctrl->setParameter($this, 'new_type', ilOpenCastPlugin::XOCT);

		return array( self::CFORM_NEW => $this->initCreateForm($a_new_type) );
	}


	/**
	 * @param string $type
	 * @param bool|false $from_post
	 * @return xoctSeriesFormGUI
	 */
	public function initCreateForm($type, $from_post = false) {
		$creation_form = new xoctSeriesFormGUI($this, new xoctOpenCast());
		if ($from_post) {
			$creation_form->setValuesByPost();
		} else {
			$creation_form->fillForm();
			if (ilObjOpenCast::DEV) {
				$creation_form->fillFormRandomized();
			}
		}

		return $creation_form->getAsPropertyFormGui();
	}


	public function save() {
		$creation_form = $this->initCreateForm($this->getType(), true);

		if ($_POST['channel_type'] == xoctSeriesFormGUI::EXISTING_NO) {
			global $ilUser;
			// TODO: remove/move to aftersave?
			$series_producers = array(xoctUser::getInstance($ilUser)->getUserRoleName());
			$xoctAclStandardSets = new xoctAclStandardSets($series_producers);
			$creation_form->getSeries()->setAccessPolicies($xoctAclStandardSets->getAcls());
		}

		if ($return = $creation_form->saveObject()) {
			$this->saveObject($return[0], $return[1]);
		} else {
			$this->tpl->setContent($creation_form->getHTML());
		}
	}


	/**
	 * @param ilObjOpenCast $newObj
	 * @param               $additional_args
	 */
	public function afterSave(ilObjOpenCast $newObj, $additional_args) {
		global $ilUser;
		/**
		 * @var $cast xoctOpenCast
		 */
		// set object id for xoctOpenCast object
		$cast = $additional_args[0];
		$cast->setObjId($newObj->getId());
		if (xoctOpenCast::where(array( 'obj_id' => $newObj->getId() ))->hasSets()) {
			$cast->update();
		} else {
			$cast->create();
		}

		// set current user & course/group roles with the perm 'edit_videos' in series' access policy and in group 'ilias_producers'
		$producers = array();
		$producers[] = xoctUser::getInstance($ilUser);
		if ($crs_or_grp_obj = ilObjOpenCast::getParentCourseOrGroup($newObj->getRefId())) {

			//check each role (admin,tutor,member) for perm edit_videos, add to series and producer group
			foreach (array('admin', 'tutor') as $role) {
				if (ilObjOpenCastAccess::isActionAllowedForRole('edit_videos', $role, $newObj->getRefId())) {
					$getter_method = "get{$role}s";
					foreach ($crs_or_grp_obj->getMembersObject()->$getter_method() as $participant_id) {
						$producers[] = xoctUser::getInstance($participant_id);
					}
				}
			}
		}
		$cast->getSeries()->addProducers($producers);
		try {
			$ilias_producers = xoctGroup::find(xoctConf::get(xoctConf::F_GROUP_PRODUCERS));
			$ilias_producers->addMembers($producers);
		} catch (xoctException $e) {
			//TODO log?
		}

		if ($cast->hasDuplicatesOnSystem()) {
			ilUtil::sendInfo($this->pl->txt('msg_info_multiple_aftersave'), true);
		}

		// checkbox from creation gui to activate "upload" permission for members
		$is_memberupload_enabled = $additional_args[1];
		if ($is_memberupload_enabled) {
			ilObjOpenCastAccess::activateMemberUpload($newObj->getRefId());
		}



		$newObj->setTitle($cast->getSeries()->getTitle());
		$newObj->setDescription($cast->getSeries()->getDescription());
		$newObj->update();

		parent::afterSave($newObj);
	}


	/**
	 * @return xoctOpenCast
	 */
	protected function initHeader($render_locator = true) {
		if ($render_locator) {
			$this->setLocator();
		}

		/**
		 * @var $xoctOpenCast xoctOpenCast
		 * @var $xoctSeries   xoctSeries
		 */
		$xoctOpenCast = xoctOpenCast::find($this->obj_id);

		if ($xoctOpenCast instanceof xoctOpenCast && $this->object) {
			$this->tpl->setTitle($xoctOpenCast->getSeries()->getTitle());
			$this->tpl->setDescription($xoctOpenCast->getSeries()->getDescription());
			if ($this->access->checkAccess('read', '', $_GET['ref_id'])) {
				$this->history->addItem($_GET['ref_id'], $this->ctrl->getLinkTarget($this, $this->getStandardCmd()), $this->getType(), $xoctOpenCast->getSeries()
				                                                                                                                                    ->getTitle());
			}
			require_once('./Services/Object/classes/class.ilObjectListGUIFactory.php');
			$list_gui = ilObjectListGUIFactory::_getListGUIByType('xoct');
			/**
			 * @var $list_gui ilObjOpenCastListGUI
			 */
			if (!$xoctOpenCast->isObjOnline()) {
				$this->tpl->setAlertProperties($list_gui->getAlertProperties());
			}
		} else {
			$this->tpl->setTitle($this->pl->txt('series_create'));
		}
		$this->tpl->setTitleIcon(ilObjOpenCast::_getIcon($this->object_id));
		$this->tpl->setPermanentLink('xoct', $_GET['ref_id']);

		return $xoctOpenCast;
	}


	/**
	 * show information screen
	 */
	function infoScreen() {
		global $lng, $ilCtrl, $ilTabs, $tree;
		/**
		 * @var $xoctOpenCast xoctOpenCast
		 * @var $item         xoctOpenCast
		 * @var $tree         ilTree
		 */
		$ilTabs->setTabActive("info_short");
		$this->initHeader(false);
		$this->checkPermission("visible");

		include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
		$info = new ilInfoScreenGUI($this);
		$info->enablePrivateNotes();
		$xoctOpenCast = xoctOpenCast::find($this->obj_id);
		$activeRecordList = xoctOpenCast::where(array( 'series_identifier' => $xoctOpenCast->getSeriesIdentifier() ))->where('obj_id != 0');
		if ($xoctOpenCast->hasDuplicatesOnSystem()) {
			$info->addSection($this->pl->txt('info_linked_items'));
			$i = 1;
			foreach ($activeRecordList->get() as $item) {
				$refs = ilObject2::_getAllReferences($item->getObjId());
				foreach ($refs as $ref) {
					$parent = $tree->getParentId($ref);
					$info->addProperty(($i) . '. '
					                   . $this->pl->txt('info_linked_item'), ilObject2::_lookupTitle(ilObject2::_lookupObjId($parent)), ilLink::_getStaticLink($parent));
					$i ++;
				}
			}
		}


		// general information
		$lng->loadLanguageModule("meta");

		$this->addInfoItems($info);

		// forward the command
		$ret = $ilCtrl->forwardCommand($info);
		//		$this->initHeader();
	}
}

?>