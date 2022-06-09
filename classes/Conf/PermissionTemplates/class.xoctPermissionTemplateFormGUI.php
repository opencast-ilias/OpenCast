<?php

use srag\DIC\OpencastObject\DICTrait;
use srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate;

/**
 * Class xoctPermissionTemplateFormGUI
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctPermissionTemplateFormGUI extends ilPropertyFormGUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpencastObjectPlugin::class;

    const F_DEFAULT = 'is_default';
	const F_TITLE_DE = 'title_de';
	const F_TITLE_EN = 'title_en';
	const F_INFO_DE = 'info_de';
	const F_INFO_EN = 'info_en';
	const F_ROLE = 'role';
	const F_READ = 'read';
	const F_WRITE = 'write';
	const F_ADDITIONAL_ACL_ACTIONS = 'additional_acl_actions';
	const F_ADDITIONAL_ACTIONS_DOWNLOAD = 'additional_actions_download';
	const F_ADDITIONAL_ACTIONS_ANNOTATE = 'additional_actions_annotate';

	/**
	 * @var  PermissionTemplate
	 */
	protected $object;
	/**
	 * @var xoctPermissionTemplateGUI
	 */
	protected $parent_gui;
	/**
	 * @var bool
	 */
	protected $is_new;

	/**
	 * @param xoctPermissionTemplateGUI $parent_gui
	 * @param PermissionTemplate $xoctPermissionTemplate
	 */
	public function __construct($parent_gui, PermissionTemplate $xoctPermissionTemplate) {
		parent::__construct();
		$this->object = $xoctPermissionTemplate;
		$this->parent_gui = $parent_gui;
		self::dic()->ctrl()->saveParameter($parent_gui, xoctPermissionTemplateGUI::IDENTIFIER);
		$this->is_new = ($this->object->getId() == '');
		$this->initForm();
	}


	/**
	 *
	 */
	protected function initForm() {
		$this->setFormAction(self::dic()->ctrl()->getFormAction($this->parent_gui));
		$this->initButtons();

		$input = new ilCheckboxInputGUI($this->txt(self::F_DEFAULT), self::F_DEFAULT);
		$input->setInfo($this->txt(self::F_DEFAULT . '_info'));
		$this->addItem($input);

		$input = new ilTextInputGUI($this->txt(self::F_TITLE_DE), self::F_TITLE_DE);
		$input->setInfo($this->txt(self::F_TITLE_DE . '_info'));
		$input->setRequired(true);
		$this->addItem($input);

		$input = new ilTextInputGUI($this->txt(self::F_TITLE_EN), self::F_TITLE_EN);
		$input->setInfo($this->txt(self::F_TITLE_EN . '_info'));
		$input->setRequired(true);
		$this->addItem($input);

		$input = new ilTextAreaInputGUI($this->txt(self::F_INFO_DE), self::F_INFO_DE);
        $input->setInfo($this->txt(self::F_INFO_DE . '_info'));
		$input->setRequired(false);
		$this->addItem($input);

		$input = new ilTextAreaInputGUI($this->txt(self::F_INFO_EN), self::F_INFO_EN);
        $input->setInfo($this->txt(self::F_INFO_EN . '_info'));
        $input->setRequired(false);
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
		$this->addItem($input);

		$input = new ilTextInputGUI($this->txt(self::F_ADDITIONAL_ACTIONS_DOWNLOAD), self::F_ADDITIONAL_ACTIONS_DOWNLOAD);
		$input->setInfo($this->txt(self::F_ADDITIONAL_ACTIONS_DOWNLOAD . '_info'));
		$this->addItem($input);

		$input = new ilTextInputGUI($this->txt(self::F_ADDITIONAL_ACTIONS_ANNOTATE), self::F_ADDITIONAL_ACTIONS_ANNOTATE);
		$input->setInfo($this->txt(self::F_ADDITIONAL_ACTIONS_ANNOTATE . '_info'));
		$this->addItem($input);
	}

	/**
	 *
	 */
	protected function initButtons() {
	    self::dic()->ctrl()->setParameter($this->parent_gui, 'subtab_active', xoctPermissionTemplateGUI::SUBTAB_PERMISSION_TEMPLATES);
		if ($this->is_new) {
			$this->setTitle(self::dic()->language()->txt('create'));
			$this->addCommandButton(xoctPermissionTemplateGUI::CMD_CREATE, self::dic()->language()->txt(xoctPermissionTemplateGUI::CMD_CREATE));
		} else {
			$this->setTitle(self::dic()->language()->txt('edit'));
			$this->addCommandButton(xoctPermissionTemplateGUI::CMD_UPDATE_TEMPLATE, self::dic()->language()->txt(xoctPermissionTemplateGUI::CMD_UPDATE));
		}

		$this->addCommandButton(xoctPermissionTemplateGUI::CMD_CANCEL, self::dic()->language()->txt(xoctPermissionTemplateGUI::CMD_CANCEL));
	}

	public function fillForm() {
		$array = array(
			self::F_DEFAULT => $this->object->isDefault(),
			self::F_TITLE_DE => $this->object->getTitleDE(),
			self::F_TITLE_EN => $this->object->getTitleEN(),
			self::F_INFO_DE => $this->object->getInfoDE(),
			self::F_INFO_EN => $this->object->getInfoEN(),
			self::F_ROLE => $this->object->getRole(),
			self::F_READ => $this->object->getRead(),
			self::F_WRITE => $this->object->getWrite(),
			self::F_ADDITIONAL_ACL_ACTIONS => $this->object->getAdditionalAclActions(),
			self::F_ADDITIONAL_ACTIONS_DOWNLOAD => $this->object->getAdditionalActionsDownload(),
			self::F_ADDITIONAL_ACTIONS_ANNOTATE => $this->object->getAdditionalActionsAnnotate(),
		);

		$this->setValuesByArray($array);
	}

	public function saveForm() {
		if (!$this->checkInput()) {
			return false;
		}

		$this->object->setDefault($this->getInput(self::F_DEFAULT));
		$this->object->setTitleDE($this->getInput(self::F_TITLE_DE));
		$this->object->setTitleEN($this->getInput(self::F_TITLE_EN));
		$this->object->setInfoDE($this->getInput(self::F_INFO_DE));
		$this->object->setInfoEN($this->getInput(self::F_INFO_EN));
		$this->object->setRole($this->getInput(self::F_ROLE));
		$this->object->setRead($this->getInput(self::F_READ));
		$this->object->setWrite($this->getInput(self::F_WRITE));
		$this->object->setAdditionalAclActions($this->getInput(self::F_ADDITIONAL_ACL_ACTIONS));
		$this->object->setAdditionalActionsDownload($this->getInput(self::F_ADDITIONAL_ACTIONS_DOWNLOAD));
		$this->object->setAdditionalActionsAnnotate($this->getInput(self::F_ADDITIONAL_ACTIONS_ANNOTATE));

		// reset other default template(s) if this one is set as default
        if ($this->getInput(self::F_DEFAULT)) {
           foreach(PermissionTemplate::where(array('is_default' => 1))->get() as $default_template) {
               /** @var $default_template PermissionTemplate */
               $default_template->setDefault(0);
               $default_template->update();
           }

        }

        $this->object->store();


        return true;
	}

	/**
	 * @param $lang_var
	 *
	 * @return string
	 */
	protected function txt($lang_var) {
		return self::plugin()->getPluginObject()->txt('perm_tpl_form_' . $lang_var);
	}
}
