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
	/**
	 * @var ilObjOpenCast
	 */
	public $object;


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
		if ($this->object) {
			xoctRequest::$series_owner = $this->object->getOwner();
		}
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
			$cmd = $this->ctrl->getCmd();
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
					// workaround for object deletion; 'parent::executeCommand()' shows the template and leads to "Headers already sent" error
					if ($next_class == "" && $cmd == 'deleteObject') {
						$this->deleteObject();
						break;
					}
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
		if (!ilObjOpenCast::_getParentCourseOrGroup($_GET['ref_id'])) {
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
			$xoctAclStandardSets = new xoctAclStandardSets();
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
	public function afterSave(ilObject $newObj) {
		global $ilUser, $rbacreview;
		/**
		 * @var $cast xoctOpenCast
		 */
		// set object id for xoctOpenCast object
		$args = func_get_args();
		$additional_args = $args[1];
		$cast = $additional_args[0];
		$cast->setObjId($newObj->getId());
		if (xoctOpenCast::where(array( 'obj_id' => $newObj->getId() ))->hasSets()) {
			$cast->update();
		} else {
			$cast->create();
		}

		// set current user & course/group roles with the perm 'edit_videos' in series' access policy and in group 'ilias_producers'
		$producers = ilObjOpenCastAccess::getProducersForRefID($newObj->getRefId());
		$producers[] = xoctUser::getInstance($ilUser);

		try {
			$ilias_producers = xoctGroup::find(xoctConf::getConfig(xoctConf::F_GROUP_PRODUCERS));
			$ilias_producers->addMembers($producers);
		} catch (xoctException $e) {
			//TODO log?
		}
		$cast->getSeries()->addProducers($producers);

		if ($cast->getDuplicatesOnSystem()) {
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
			if (!$xoctOpenCast->isOnline()) {
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
		if ($refs = $xoctOpenCast->getDuplicatesOnSystem()) {
			$info->addSection($this->pl->txt('info_linked_items'));
			$i = 1;
			foreach ($refs as $ref) {
				$parent = $tree->getParentId($ref);
				$info->addProperty(($i) . '. '
				                   . $this->pl->txt('info_linked_item'), ilObject2::_lookupTitle(ilObject2::_lookupObjId($parent)), ilLink::_getStaticLink($parent));
				$i ++;
			}
		}


		// general information
		$lng->loadLanguageModule("meta");

		$this->addInfoItems($info);

		// forward the command
		$ret = $ilCtrl->forwardCommand($info);
		//		$this->initHeader();
	}


	/**
	 * Overwritten/copied to allow recognition of duplicates and show them in delete confirmation
	 *
	 * @param bool $a_error
	 */
	public function deleteObject($a_error = false) {
		global $ilCtrl;

		if ($_GET["item_ref_id"] != "")
		{
			$_POST["id"] = array($_GET["item_ref_id"]);
		}

		if(is_array($_POST["id"]))
		{
			foreach($_POST["id"] as $idx => $id)
			{
				$_POST["id"][$idx] = (int)$id;
			}
		}

		// SAVE POST VALUES (get rid of this
		ilSession::set("saved_post", $_POST["id"]);

		if (!$this->showDeleteConfirmation($_POST["id"], $a_error))
		{
			$ilCtrl->returnToParent($this);
		}
	}

	/**
	 * Overwritten/copied to allow recognition of duplicates and show them in delete confirmation
	 */
	function showDeleteConfirmation($a_ids, $a_supress_message = false)
	{
		global $lng, $ilSetting, $ilCtrl, $tpl, $objDefinition;

		if (!is_array($a_ids) || count($a_ids) == 0)
		{
			ilUtil::sendFailure($lng->txt("no_checkbox"), true);
			return false;
		}

		// Remove duplicate entries
		$a_ids = array_unique((array) $a_ids);

		include_once("./Services/Utilities/classes/class.ilConfirmationGUI.php");
		$cgui = new ilConfirmationGUI();

		if(!$a_supress_message)
		{
			$msg = $lng->txt("info_delete_sure");

			if (!$ilSetting->get('enable_trash'))
			{
				$msg .= "<br/>".$lng->txt("info_delete_warning_no_trash");
			}

			$cgui->setHeaderText($msg);
		}
		$cgui->setFormAction($ilCtrl->getFormAction($this));
		$cgui->setCancel($lng->txt("cancel"), "cancelDelete");
		$cgui->setConfirm($lng->txt("confirm"), "confirmedDelete");

		$form_name = "cgui_".md5(uniqid());
		$cgui->setFormName($form_name);

		$deps = array();
		foreach ($a_ids as $ref_id)
		{
			$obj_id = ilObject::_lookupObjId($ref_id);
			$type = ilObject::_lookupType($obj_id);
			$title = call_user_func(array(ilObjectFactory::getClassByType($type),'_lookupTitle'),$obj_id);
			$alt = $lng->txt("icon")." ".ilPlugin::lookupTxt("rep_robj", $type, "obj_".$type);

			$title .= $this->handleMultiReferences($obj_id, $ref_id, $form_name);

			$cgui->addItem("id[]", $ref_id, $title,
				ilObject::_getIcon($obj_id, "small", $type),
				$alt);

			ilObject::collectDeletionDependencies($deps, $ref_id, $obj_id, $type);
		}
		$deps_html = "";

		if (is_array($deps) && count($deps) > 0)
		{
			include_once("./Services/Repository/classes/class.ilRepDependenciesTableGUI.php");
			$tab = new ilRepDependenciesTableGUI($deps);
			$deps_html = "<br/><br/>".$tab->getHTML();
		}

		$tpl->setContent($cgui->getHTML().$deps_html);
		return true;
	}

	/**
	 * Overwritten/copied to allow recognition of duplicates and show them in delete confirmation
	 *
	 * @param int $a_obj_id
	 * @param int $a_ref_id
	 * @param string $a_form_name
	 * @return string
	 */
	function handleMultiReferences($a_obj_id, $a_ref_id, $a_form_name)
	{
		global $lng, $ilAccess, $tree;

		// process

		/** @var xoctOpenCast $xoctOpenCast */
		$xoctOpenCast = xoctOpenCast::find($a_obj_id);
		if($all_refs = $xoctOpenCast->getDuplicatesOnSystem())
		{
			$lng->loadLanguageModule("rep");

			$may_delete_any = 0;
			$counter = 0;
			$items = array();
			foreach($all_refs as $mref_id)
			{
				// not the already selected reference, no refs from trash
				if($mref_id != $a_ref_id && !$tree->isDeleted($mref_id))
				{
					if($ilAccess->checkAccess("read", "", $mref_id))
					{
						$may_delete = false;
						if($ilAccess->checkAccess("delete", "", $mref_id))
						{
							$may_delete = true;
							$may_delete_any++;
						}

						$items[] = array("id" => $mref_id,
							"path" => array_shift($this->buildPath(array($mref_id))),
							"delete" => $may_delete);
					}
					else
					{
						$counter++;
					}
				}
			}


			// render

			$tpl = new ilTemplate("tpl.rep_multi_ref.html", true, true, "Services/Repository");

			$tpl->setVariable("TXT_INTRO", $lng->txt("rep_multiple_reference_deletion_intro"));

			if($may_delete_any)
			{
				$tpl->setVariable("TXT_INSTRUCTION", $lng->txt("rep_multiple_reference_deletion_instruction"));
			}

			if($items)
			{
				$var_name = "mref_id[]";

				foreach($items as $item)
				{
					if($item["delete"])
					{
						$tpl->setCurrentBlock("cbox");
						$tpl->setVariable("ITEM_NAME", $var_name);
						$tpl->setVariable("ITEM_VALUE", $item["id"]);
						$tpl->parseCurrentBlock();
					}
					else
					{
						$tpl->setCurrentBlock("item_info");
						$tpl->setVariable("TXT_ITEM_INFO", $lng->txt("rep_no_permission_to_delete"));
						$tpl->parseCurrentBlock();
					}

					$tpl->setCurrentBlock("item");
					$tpl->setVariable("ITEM_TITLE", $item["path"]);
					$tpl->parseCurrentBlock();
				}

				if($may_delete_any > 1)
				{
					$tpl->setCurrentBlock("cbox");
					$tpl->setVariable("ITEM_NAME", "sall_".$a_ref_id);
					$tpl->setVariable("ITEM_VALUE", "");
					$tpl->setVariable("ITEM_ADD", " onclick=\"il.Util.setChecked('".
						$a_form_name."', '".$var_name."', document.".$a_form_name.
						".sall_".$a_ref_id.".checked)\"");
					$tpl->parseCurrentBlock();

					$tpl->setCurrentBlock("item");
					$tpl->setVariable("ITEM_TITLE", $lng->txt("select_all"));
					$tpl->parseCurrentBlock();
				}
			}

			if($counter)
			{
				$tpl->setCurrentBlock("add_info");
				$tpl->setVariable("TXT_ADDITIONAL_INFO",
					sprintf($lng->txt("rep_object_references_cannot_be_read"), $counter));
				$tpl->parseCurrentBlock();
			}

			return $tpl->get();
		}
	}

	/**
	 * Overwritten/copied to allow recognition of duplicates and show them in delete confirmation
	 *
	 * @param	array	$ref_ids
	 * @return	array
	 */
	protected function buildPath($ref_ids)
	{
		global $tree;

		include_once 'Services/Link/classes/class.ilLink.php';

		if(!count($ref_ids))
		{
			return false;
		}

		$result = array();
		foreach($ref_ids as $ref_id)
		{
			$path = "";
			$path_full = $tree->getPathFull($ref_id);
			foreach($path_full as $idx => $data)
			{
				if($idx)
				{
					$path .= " &raquo; ";
				}
				if($ref_id != $data['ref_id'])
				{
					$path .= $data['title'];
				}
				else
				{
					$path .= ('<a target="_top" href="'.
						ilLink::_getLink($data['ref_id'],$data['type']).'">'.
						$data['title'].'</a>');
				}

			}

			$result[] = $path;
		}
		return $result;
	}

}