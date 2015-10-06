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
	const TAB_EVENTS = 'series';
	const TAB_SETTINGS = 'settings';
	const TAB_INFO = 'info';
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


	public function executeCommand() {
		try {
			xoctConf::setApiSettings();
			$cmd = $this->ctrl->getCmd();
			$next_class = $this->ctrl->getNextClass($this);
			$this->tpl->getStandardTemplate();
			$xoctOpenCast = $this->initHeader();

			switch ($next_class) {
				case 'ilpermissiongui':
					$this->tabs_gui->setTabActive('id_permissions');
					$perm_gui = new ilPermissionGUI($this);
					//				$perm_gui->
					$this->ctrl->forwardCommand($perm_gui);
					$this->tpl->show();
					break;
				case 'ilinfoscreengui':
					$info_gui = new ilInfoScreenGUI($this);
					$this->ctrl->forwardCommand($info_gui);
					$this->tpl->show();
					break;
				case 'xoctseriesgui':
					$xoctSeriesGUI = new xoctSeriesGUI($xoctOpenCast);
					$this->ctrl->forwardCommand($xoctSeriesGUI);
					$this->tpl->show();
					break;
				case 'xocteventgui':
					$xoctEventGUI = new xoctEventGUI($xoctOpenCast);
					$this->ctrl->forwardCommand($xoctEventGUI);
					$this->tpl->show();
					break;
				case 'xoctgroupgui':
					$xoctGroupGUI = new xoctGroupGUI($xoctOpenCast);
					$this->ctrl->forwardCommand($xoctGroupGUI);
					$this->tpl->show();
					break;
				case 'xoctgroupparticipantgui':
					$xoctGroupParticipantGUI = new xoctGroupParticipantGUI($xoctOpenCast);
					$this->ctrl->forwardCommand($xoctGroupParticipantGUI);
					$this->tpl->show();
					break;
				case 'xoctinvitationgui':
					$this->tabs_gui->clearTargets();
					$this->tabs_gui->setBackTarget($this->pl->txt('invitations_back'), $this->ctrl->getLinkTargetByClass('xoctEventGUI'));
					$xoctGroupGUI = new xoctInvitationGUI($xoctOpenCast);
					$this->ctrl->forwardCommand($xoctGroupGUI);
					$this->tpl->show();
					break;
				case 'ilObjOpenCastGUI':
				case '':
					switch ($cmd) {
						case 'create':
							$this->tabs_gui->clearTargets();
							$this->create();
							break;
						case 'save':
							$this->save();
							break;
						case 'edit':
							$this->edit();
							break;
						case 'update':
							parent::update();
							break;
						case self::CMD_SHOW_CONTENT:
							$this->showContent();
							$this->tpl->show();
							break;
						case 'cancel':
							$this->ctrl->returnToParent($this);
							break;
						case 'infoScreen':
							//						exit;
							$this->tabs_gui->setTabActive(self::TAB_INFO);
							$this->ctrl->setCmd('showSummary');
							$this->ctrl->setCmdClass('ilinfoscreengui');
							$this->infoScreen();
							$this->tpl->show();
							break;
						default:
							$this->ctrl->redirect(new xoctEventGUI($xoctOpenCast));
					}
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
			$this->tabs_gui->addTab("id_permissions", $lng->txt("perm_settings"), $this->ctrl->getLinkTargetByClass(array(
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

		//$creation_form->fillFormRandomized();

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
			$creation_form->setValuesByPost();
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

		parent::afterSave($newObj);
	}


	public function infoScreen() {
		$info = new ilInfoScreenGUI($this);
		$info->addSection($this->txt('series_metadata'));
		$xoctOpenCast = xoctOpenCast::find($this->obj_id);
		$xoctOpenCastFormGUI = new xoctSeriesFormGUI($this, $xoctOpenCast, true, true);
		$xoctOpenCastFormGUI->fillForm();
		/**
		 * @var $item ilTextInputGUI
		 */
		foreach ($xoctOpenCastFormGUI->getItems() as $item) {
			//$info->addProperty($item->getTitle(), $item->getValue());
		}

		$info->enablePrivateNotes();
		$this->addInfoItems($info);
		$this->ctrl->forwardCommand($info);
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
			$this->tpl->setTitle($this->object->getTitle());
			$this->tpl->setDescription($this->object->getDescription());
			if ($this->access->checkAccess('read', '', $_GET['ref_id'])) {
				$this->history->addItem($_GET['ref_id'], $this->ctrl->getLinkTarget($this, $this->getStandardCmd()), $this->getType(), '');
			}
			require_once('./Services/Object/classes/class.ilObjectListGUIFactory.php');
			$list_gui = ilObjectListGUIFactory::_getListGUIByType('xoct');
			/**
			 * @var $list_gui ilObjOpenCastListGUI
			 */
			if ($xoctOpenCast->isObjOnline()) {
				$this->tpl->setAlertProperties($list_gui->getAlertProperties());
			}
		} else {
			$this->tpl->setTitle($this->pl->txt('series_create'));
		}
		$this->tpl->setTitleIcon(ilUtil::getImagePath('icon_xoct.svg', 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast'));
		$this->setTabs();
		$this->tpl->setPermanentLink('xoct', $_GET['ref_id']);

		return $xoctOpenCast;
	}

	//	public function confirmDeleteObject() {
	//		$a_val = array( $_GET['ref_id'] );
	//		ilSession::set('saved_post', $a_val);
	//		$ru = new ilRepUtilGUI($this);
	//		if (! $ru->showDeleteConfirmation($a_val, false)) {
	//			$this->redirectParentGui();
	//		}
	//		$this->tpl->show();
	//	}
	//
	//
	//	public function confirmedDelete() {
	//		if (isset($_POST['mref_id'])) {
	//			$_SESSION['saved_post'] = array_unique(array_merge($_SESSION['saved_post'], $_POST['mref_id']));
	//		}
	//		$ref_id = $_SESSION['saved_post'][0];
	//		$parent_ref_id = $this->getParentRefId($ref_id);
	//		$xoctRequest = xoctRequest::getInstanceForOpenCastObjectId(ilObject2::_lookupObjId($ref_id));
	//		$xoctRequest->setStatus(xoctRequest::STATUS_DELETED);
	//		$xoctRequest->update();
	//		$ru = new ilRepUtilGUI($this);
	//		$ru->deleteObjects(ilObjOpenCast::returnParentCrsRefId($_GET['ref_id']), ilSession::get('saved_post'));
	//		ilSession::clear('saved_post');
	//		ilUtil::redirect(ilLink::_getLink($parent_ref_id));
	//	}
}

?>