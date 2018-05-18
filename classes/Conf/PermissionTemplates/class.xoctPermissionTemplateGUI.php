<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */
/**
 * Class xoctPermissionTemplateGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctPermissionTemplateGUI: xoctMainGUI
 */
class xoctPermissionTemplateGUI extends xoctGUI {

	const IDENTIFIER = 'tpl_id';


	/**
	 *
	 */
	protected function index() {
		$xoctPermissionTemplateTableGUI = new xoctPermissionTemplateTableGUI($this);
		$xoctVideoPortalSettingsFormGUI = new xoctVideoPortalSettingsFormGUI($this);
		$xoctVideoPortalSettingsFormGUI->fillForm();
		$this->tpl->setContent($xoctVideoPortalSettingsFormGUI->getHTML() . $xoctPermissionTemplateTableGUI->getHTML());
	}


	/**
	 *
	 */
	protected function add() {
		$xoctPermissionTemplateFormGUI = new xoctPermissionTemplateFormGUI($this,new xoctPermissionTemplate());
		$this->tpl->setContent($xoctPermissionTemplateFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function create() {
		$xoctPermissionTemplateFormGUI = new xoctPermissionTemplateFormGUI($this,new xoctPermissionTemplate());
		$xoctPermissionTemplateFormGUI->setValuesByPost();
		if ($xoctPermissionTemplateFormGUI->saveForm()) {
			ilUtil::sendSuccess($this->pl->txt('config_msg_success'), true);
			$this->ctrl->redirect($this);
		}
		$this->tpl->setContent($xoctPermissionTemplateFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function edit() {
		$xoctPermissionTemplateFormGUI = new xoctPermissionTemplateFormGUI($this,xoctPermissionTemplate::find($_GET[self::IDENTIFIER]));
		$xoctPermissionTemplateFormGUI->fillForm();
		$this->tpl->setContent($xoctPermissionTemplateFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function update() {
		$xoctPermissionTemplateFormGUI = new xoctPermissionTemplateFormGUI($this,xoctPermissionTemplate::find($_GET[self::IDENTIFIER]));
		$xoctPermissionTemplateFormGUI->setValuesByPost();
		if ($xoctPermissionTemplateFormGUI->saveForm()) {
			ilUtil::sendSuccess($this->pl->txt('config_msg_success'), true);
			$this->ctrl->redirect($this);
		}
		$this->tpl->setContent($xoctPermissionTemplateFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function confirmDelete() {
		// TODO: Implement confirmDelete() method.
	}


	/**
	 *
	 */
	protected function delete() {
		// TODO: Implement delete() method.
	}


	/**
	 * ajax
	 */
	protected function reorder() {
		$ids = $_POST['ids'];
		$sort = 1;
		foreach ($ids as $id) {
			/** @var xoctPermissionTemplate $perm_tpl */
			$perm_tpl = xoctPermissionTemplate::find($id);
			$perm_tpl->setSort($sort);
			$perm_tpl->update();
			$sort++;
		}
		exit;
	}

    /**
     * @param $key
     *
     * @return string
     */
    public function txt($key) {
        return $this->pl->txt('config_' . $key);
    }
}