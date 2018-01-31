<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class xoctPermissionTemplateFormGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctPermissionTemplateFormGUI extends ilPropertyFormGUI {

	const F_TITLE = 'title';
	const F_INFO = 'info';
	const F_ROLE = 'role';
	const F_READ = 'read';
	const F_WRITE = 'write';
	const F_ADDITIONAL_ACL_ACTIONS = 'additional_acl_actions';

	/**
	 * @var  xoctPermissionTemplate
	 */
	protected $object;
	/**
	 * @var xoctPermissionTemplateGUI
	 */
	protected $parent_gui;
	/**
	 * @var  ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilOpenCastPlugin
	 */
	protected $pl;
	/**
	 * @var ilLanguage
	 */
	protected $lng;
	/**
	 * @var bool
	 */
	protected $is_new;

	/**
	 * @param xoctPermissionTemplateGUI $parent_gui
	 * @param xoctPermissionTemplate $xoctPermissionTemplate
	 */
	public function __construct($parent_gui, xoctPermissionTemplate $xoctPermissionTemplate) {
		global $ilCtrl, $lng;
		$this->object = $xoctPermissionTemplate;
		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->pl = ilOpenCastPlugin::getInstance();
		$this->ctrl->saveParameter($parent_gui, xoctPermissionTemplateGUI::IDENTIFIER);
		$this->lng = $lng;
		$this->is_new = ($this->object->getId() == '');
		$this->initForm();
	}


	/**
	 *
	 */
	protected function initForm() {
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		$this->initButtons();

		$input = new ilTextInputGUI($this->txt(self::F_TITLE), self::F_TITLE);
		$input->setInfo($this->txt(self::F_TITLE . '_info'));
		$input->setRequired(true);
		$this->addItem($input);

		$input = new ilTextInputGUI($this->txt(self::F_INFO), self::F_INFO);
		$input->setInfo($this->txt(self::F_INFO . '_info'));
		$input->setRequired(true);
		$this->addItem($input);

		$input = new ilTextInputGUI($this->txt(self::F_ROLE), self::F_ROLE);
		$input->setInfo($this->txt(self::F_ROLE . '_info'));
		$input->setRequired(true);
		$this->addItem($input);

		$input = new ilCheckboxInputGUI($this->txt(self::F_READ), self::F_READ);
		$input->setInfo($this->txt(self::F_READ . '_info'));
		$this->addItem($input);

		$input = new ilCheckboxInputGUI($this->txt(self::F_WRITE), self::F_WRITE);
		$input->setInfo($this->txt(self::F_WRITE . '_info'));
		$this->addItem($input);

		$input = new ilTextInputGUI($this->txt(self::F_ADDITIONAL_ACL_ACTIONS), self::F_ADDITIONAL_ACL_ACTIONS);
		$input->setInfo($this->txt(self::F_ADDITIONAL_ACL_ACTIONS . '_info'));
		$input->setRequired(true);
		$this->addItem($input);
	}

	/**
	 *
	 */
	protected function initButtons() {
		if ($this->is_new) {
			$this->setTitle($this->lng->txt('create'));
			$this->addCommandButton(xoctPermissionTemplateGUI::CMD_CREATE, $this->lng->txt(xoctPermissionTemplateGUI::CMD_CREATE));
		} else {
			$this->setTitle($this->lng->txt('edit'));
			$this->addCommandButton(xoctPermissionTemplateGUI::CMD_UPDATE, $this->lng->txt(xoctPermissionTemplateGUI::CMD_UPDATE));
		}

		$this->addCommandButton(xoctPermissionTemplateGUI::CMD_CANCEL, $this->lng->txt(xoctPermissionTemplateGUI::CMD_CANCEL));
	}

	public function fillForm() {
		$array = array(
			self::F_TITLE => $this->object->getTitle(),
			self::F_INFO => $this->object->getInfo(),
			self::F_ROLE => $this->object->getRole(),
			self::F_READ => $this->object->getRead(),
			self::F_WRITE => $this->object->getWrite(),
			self::F_ADDITIONAL_ACL_ACTIONS => $this->object->getAdditionalAclActions(),
		);

		$this->setValuesByArray($array);
	}

	public function saveForm() {
		if (!$this->checkInput()) {
			return false;
		}

		$this->object->setTitle($this->getInput(self::F_TITLE));
		$this->object->setInfo($this->getInput(self::F_INFO));
		$this->object->setRole($this->getInput(self::F_ROLE));
		$this->object->setRead($this->getInput(self::F_READ));
		$this->object->setWrite($this->getInput(self::F_WRITE));
		$this->object->setAdditionalAclActions($this->getInput(self::F_ADDITIONAL_ACL_ACTIONS));

		$this->object->store();
		return true;
	}

	/**
	 * @param $lang_var
	 *
	 * @return string
	 */
	protected function txt($lang_var) {
		return $this->pl->txt('perm_tpl_form_' . $lang_var);
	}
}