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


require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/class.xoctSeriesFormGUI.php');
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


//	/**
//	 * @param ilObjOpenCast $newObj
//	 * @param              $additional_args
//	 */
//	public function afterSave(ilObjOpenCast $newObj, $additional_args) {
////		$request = new xoctRequest($additional_args[0]);
////		$request->setOpenCastObjectId($newObj->getId());
////		$request->update();
//
//		parent::afterSave($newObj);
//	}
//
//
	/**
	 * @return string
	 */
	final function getType() {
		return ilOpenCastPlugin::XOCT;
	}
//
//
//	public function executeCommand() {
////		if ($this->access->checkAccess('read', '', $_GET['ref_id'])) {
////			$this->history->addItem($_GET['ref_id'], $this->ctrl->getLinkTarget($this, $this->getStandardCmd()), $this->getType(), '');
////		}
////		$cmd = $this->ctrl->getCmd();
////		$next_class = $this->ctrl->getNextClass($this);
////		$this->tpl->getStandardTemplate();
////
////		$xoctRequest = xoctRequest::getInstanceForOpenCastObjectId($this->obj_id);
////
////		if ($xoctRequest->getId()) {
////			self::initHeader($xoctRequest->getTitle());
////		} else {
////
////			self::initHeader($this->pl->txt('obj_xoct_title'));
////		}
////
////		switch ($next_class) {
////			case 'ilpermissiongui':
////				$this->setTabs();
////				$this->tabs_gui->setTabActive('permissions');
////				$perm_gui = new ilPermissionGUI($this);
////				$this->ctrl->forwardCommand($perm_gui);
////				break;
////			case 'ilinfoscreengui':
////				$this->setTabs();
////				$this->tabs_gui->setTabActive('info');
////				$info_gui = new ilInfoScreenGUI($this);
////				$this->ctrl->forwardCommand($info_gui);
////				break;
////			case 'srobjOpenCastgui':
////			case '':
////				$this->setTabs();
////				switch ($cmd) {
////					case 'create':
////						$this->tabs_gui->clearTargets();
////						$this->create();
////						break;
////					case 'save':
////						$this->save();
////						break;
////					case self::CMD_REDIRECT_PARENT_GUI:
////						$this->redirectParentGui();
////						break;
////					case 'edit':
////						// case 'update':
////						$this->edit();
////						break;
////					case 'update':
////						parent::update();
////						break;
////					case 'sendFile':
////						$this->$cmd();
////						break;
////
////					case self::CMD_SHOW_CONTENT:
////					case '':
////					case 'cancel':
////						$this->ctrl->returnToParent($this);
////						break;
////					case 'infoScreen':
////						$this->ctrl->setCmd('showSummary');
////						$this->ctrl->setCmdClass('ilinfoscreengui');
////						$this->infoScreen();
////						$this->tpl->show();
////						break;
////					case self::CMD_CONFIRM_DELETE_OBJECT:
////						$this->$cmd();
////						break;
////					case self::CMD_DELETE_DIGI_LIT:
////						$this->confirmedDelete();
////						break;
////				}
////				break;
////		}
//	}
//
//
	/**
	 * @return string
	 */
	function getAfterCreationCmd() {
		return self::CMD_SHOW_CONTENT;
	}
//
//
//	public function redirectParentGui() {
//		ilUtil::redirect(ilLink::_getLink($this->getParentRefId()));
//	}
//
//
//	public function getParentRefId($ref_id = NULL) {
//		global $tree;
//		/**
//		 * @var $tree ilTree
//		 */
//		if (! $ref_id) {
//			$ref_id = $_GET['ref_id'];
//		}
//
//		return $tree->getParentId($ref_id);
//	}
//
//
	/**
	 * @return string

	 */
	function getStandardCmd() {
		return self::CMD_SHOW_CONTENT;
	}
//
//
//	protected function setTabs() {
//		return true;
//	}
//
//
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
		$creation_form = new xoctSeriesFormGUI($this, new xoctSeries());
		$creation_form->fillForm(ilObjOpenCast::returnParentCrsRefId($_GET['ref_id']));
		global $ilUser;
		/**
		 * @var $ilUser ilObjUser
		 */
		if ((strpos(gethostname(), '.local') OR strpos(gethostname(), 'vagrant-') === 0) AND $ilUser->getId() == 6) {
			$creation_form->fillFormRandomized();
		}

		return $creation_form->getAsPropertyFormGui();

		return parent::initCreateForm($type);
	}
//
//
//	public function save() {
////		$creation_form = new xoctRequestFormGUI($this, new xoctRequest());
////		$creation_form->setValuesByPost();
////		if ($request_id = $creation_form->saveObject(ilObjOpenCast::returnParentCrsRefId($_GET['ref_id']))) {
////			$this->saveObject($request_id);
////		} else {
////			$creation_form->setValuesByPost();
////			$this->tpl->setContent($creation_form->getHtml());
////		}
//	}
//
//
//
//	/**
//	 * @param $title
//	 */
//	public static function initHeader($title) {
////		global $tpl;
////		$pl = ilOpenCastPlugin::getInstance();
////		$tpl->setTitle($title);
////		$tpl->setDescription('');
////		if (xoctConfig::is50()) {
////			$tpl->setTitleIcon(ilUtil::getImagePath('icon_xoct.svg', 'Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast'));
////		} else {
////			$tpl->setTitleIcon($pl->getImagePath('icon_' . ilOpenCastPlugin::getStaticPluginPrefix() . '_b.png'), $pl->txt('xoct_icon') . ' '
////				. $pl->txt('obj_' . ilOpenCastPlugin::getStaticPluginPrefix()));
////		}
//	}

//
//	public function infoScreen() {
//		$info = new ilInfoScreenGUI($this);
////		$info->addSection($this->txt('request_metadata'));
////		$xoctRequest = xoctRequest::getInstanceForOpenCastObjectId($this->obj_id);
////		$xoctRequestFormGUI = new xoctRequestFormGUI($this, $xoctRequest, true, true);
////		$xoctRequestFormGUI->fillForm();
////		/**
////		 * @var $item ilTextInputGUI
////		 */
////		foreach ($xoctRequestFormGUI->getItems() as $item) {
////			$info->addProperty($item->getTitle(), $item->getValue());
////		}
////
////		$info->enablePrivateNotes();
////		$this->addInfoItems($info);
//		$this->ctrl->forwardCommand($info);
//	}


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