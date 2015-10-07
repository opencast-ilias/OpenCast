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
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Group/class.xoctGroupGUI.php');

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
				case 'xoctgroupgui':
				case 'xoctgroupparticipantgui':
				case 'xoctinvitationgui':
					$xoctOpenCast = $this->initHeader();
					$this->setTabs();
					$xoctSeriesGUI = new $next_class($xoctOpenCast);
					$this->ctrl->forwardCommand($xoctSeriesGUI);
					$this->tpl->show();
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
		global $lng, $ilUser;

		/**
		 * @var $xoctOpenCast xoctOpenCast
		 */
		$xoctOpenCast = xoctOpenCast::find($this->obj_id);
		if (! $xoctOpenCast instanceof xoctOpenCast) {
			return false;
		}

		$this->tabs_gui->addTab(self::TAB_EVENTS, $this->pl->txt('tab_event_index'), $this->ctrl->getLinkTarget(new xoctEventGUI(), xoctEventGUI::CMD_STANDARD));
		$this->tabs_gui->addTab(self::TAB_INFO, $this->pl->txt('tab_info'), $this->ctrl->getLinkTarget($this, 'infoScreen'));
		if ($this->checkPermissionBool('write')) {
			$this->tabs_gui->addTab(self::TAB_SETTINGS, $this->pl->txt('tab_series_settings'), $this->ctrl->getLinkTarget(new xoctSeriesGUI(), xoctSeriesGUI::CMD_EDIT));
			if ($xoctOpenCast->getPermissionPerClip()) {
				$this->tabs_gui->addTab(self::TAB_GROUPS, $this->pl->txt('tab_groups'), $this->ctrl->getLinkTarget(new xoctGroupGUI()));
			}
			if ($ilUser->getId() == 6 AND ilObjOpenCast::DEV) {
				$this->tabs_gui->addTab('migrate_event', $this->pl->txt('tab_migrate_event'), $this->ctrl->getLinkTarget(new xoctEventGUI(), 'search'));
				$this->tabs_gui->addTab('list_all', $this->pl->txt('tab_list_all'), $this->ctrl->getLinkTarget(new xoctEventGUI(), 'listAll'));
			}
		}

		if ($this->checkPermissionBool("edit_permission")) {
			$this->tabs_gui->addTab("perm_settings", $lng->txt("perm_settings"), $this->ctrl->getLinkTargetByClass(array(
				get_class($this),
				"ilpermissiongui"
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
		$this->ctrl->setParameter($this, 'new_type', ilOpenCastPlugin::XOCT);

		return array( self::CFORM_NEW => $this->initCreateForm($a_new_type) );
	}


	/**
	 * @param string $type
	 *
	 * @return ilPropertyFormGUI
	 */
	public function initCreateForm($type) {
		$creation_form = new xoctSeriesFormGUI($this, new xoctOpenCast());
		$creation_form->fillForm();

		return $creation_form->getAsPropertyFormGui();
	}


	public function save() {
		$creation_form = new xoctSeriesFormGUI($this, new xoctOpenCast());
		$creation_form->setValuesByPost();

		if ($_POST['channel_type'] == xoctSeriesFormGUI::EXISTING_NO) {
			global $ilUser;
			$xoctAclStandardSets = new xoctAclStandardSets($ilUser);
			$creation_form->getSeries()->setAccessPolicies($xoctAclStandardSets->getSeries());
		}

		if ($identifier = $creation_form->saveObject()) {
			$this->saveObject($identifier);
		} else {
			$this->tpl->setContent($creation_form->getHtml());
		}
	}


	/**
	 * @param ilObjOpenCast $newObj
	 * @param               $additional_args
	 */
	public function afterSave(ilObjOpenCast $newObj, $additional_args) {
		/**
		 * @var $cast xoctOpenCast
		 */
		$cast = $additional_args[0];
		$cast->setObjId($newObj->getId());
		if (xoctOpenCast::where(array( 'obj_id' => $newObj->getId() ))->hasSets()) {
			$cast->update();
		} else {
			$cast->create();
		}

		$newObj->setTitle($cast->getSeries()->getTitle());
		$newObj->setDescription($cast->getSeries()->getDescription());
		$newObj->update();

		parent::afterSave($newObj);
	}


	/**
	 * @return xoctOpenCast
	 */
	protected function initHeader() {
		$this->setLocator();

		/**
		 * @var $xoctOpenCast xoctOpenCast
		 * @var $xoctSeries   xoctSeries
		 */
		$xoctOpenCast = xoctOpenCast::find($this->obj_id);

		if ($xoctOpenCast instanceof xoctOpenCast && $this->object) {
			$this->tpl->setTitle($xoctOpenCast->getSeries()->getTitle());
			$this->tpl->setDescription($xoctOpenCast->getSeries()->getDescription());
			if ($this->access->checkAccess('read', '', $_GET['ref_id'])) {
				$this->history->addItem($_GET['ref_id'], $this->ctrl->getLinkTarget($this, $this->getStandardCmd()), $this->getType(), '');
			}
			require_once('./Services/Object/classes/class.ilObjectListGUIFactory.php');
			$list_gui = ilObjectListGUIFactory::_getListGUIByType('xoct');
			/**
			 * @var $list_gui ilObjOpenCastListGUI
			 */
			if (! $xoctOpenCast->isObjOnline()) {
				$this->tpl->setAlertProperties($list_gui->getAlertProperties());
			}
		} else {
			$this->tpl->setTitle($this->pl->txt('series_create'));
		}
		$this->tpl->setTitleIcon(ilUtil::getImagePath('icon_xoct.svg', 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast'));
		$this->tpl->setPermanentLink('xoct', $_GET['ref_id']);

		return $xoctOpenCast;
	}


	/**
	 * show information screen
	 */
	function infoScreen() {
		global $ilAccess, $ilUser, $lng, $ilCtrl, $tpl, $ilTabs;

		$ilTabs->setTabActive("info_short");

		$this->checkPermission("visible");

		include_once("./Services/InfoScreen/classes/class.ilInfoScreenGUI.php");
		$info = new ilInfoScreenGUI($this);
		$info->enablePrivateNotes();

		$xoctOpenCast = xoctOpenCast::find($this->obj_id);
		/**
		 * @var $xoctOpenCast xoctOpenCast
		 */
		$daily_token = strtoupper(substr(md5($xoctOpenCast->getSeriesIdentifier() . date('d-m-Y')), 0, 6));

		if (xoctConf::get(xoctConf::F_UPLOAD_TOKEN) && $xoctOpenCast->isShowUploadToken()) {
			$info->addSection($this->pl->txt('upload_token_upload_token'));
			$info->addProperty($this->pl->txt('upload_token_channel_id'), $xoctOpenCast->getSeriesIdentifier());
			$info->addProperty($this->pl->txt('upload_token_daily_upload_token'), $daily_token);
		}

		// general information
		$lng->loadLanguageModule("meta");

		$this->addInfoItems($info);

		// forward the command
		$ret = $ilCtrl->forwardCommand($info);

		$this->initHeader();
	}
}

?>