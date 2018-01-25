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
		$b = ilLinkButton::getInstance();
		$b->setCaption($this->pl->txt('add_new_permission_template'), false);
		$b->setUrl($this->ctrl->getLinkTarget($this, self::CMD_ADD));
		$this->toolbar->addButtonInstance($b);

		$xoctPermissionTemplateTableGUI = new xoctPermissionTemplateTableGUI($this);
		$this->tpl->setContent($xoctPermissionTemplateTableGUI->getHTML());
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
			ilUtil::sendSuccess($this->pl->txt('permission_template_msg_success'), true);
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
			ilUtil::sendSuccess($this->pl->txt('permission_template_msg_success'), true);
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
}