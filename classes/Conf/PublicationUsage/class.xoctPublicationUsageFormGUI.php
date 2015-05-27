<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctWaiterGUI.php');

/**
 * Class xoctPublicationUsageFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctPublicationUsageFormGUI extends ilPropertyFormGUI {

	const F_USAGE_ID = 'usage_id';
	const F_TITLE = 'title';
	const F_DESCRIPTION = 'description';
	const F_PUBLICATION_ID = 'publication_id';
	const F_STATUS = 'status';
	const F_EXT_ID = 'ext_id';
	const F_MD_TYPE = 'md_type';
	/**
	 * @var  xoctPublicationUsage
	 */
	protected $object;
	/**
	 * @var xoctPublicationUsageGUI
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
	 * @param                      $parent_gui
	 * @param xoctPublicationUsage $xoctPublicationUsage
	 */
	public function __construct($parent_gui, xoctPublicationUsage $xoctPublicationUsage) {
		global $ilCtrl, $lng, $tpl;
		$this->object = $xoctPublicationUsage;
		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->pl = ilOpenCastPlugin::getInstance();
		$this->ctrl->saveParameter($parent_gui, xoctPublicationUsageGUI::IDENTIFIER);
		$this->lng = $lng;
		$this->is_new = ($this->object->getUsageId() == '');
		$this->initForm();
	}


	protected function initForm() {
		$this->setTarget('_top');
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		$this->initButtons();

		$te = new ilTextInputGUI($this->parent_gui->txt(self::F_USAGE_ID), self::F_USAGE_ID);
		$te->setRequired(true);
		$te->setDisabled(true);
		$this->addItem($te);

		$te = new ilTextInputGUI($this->parent_gui->txt(self::F_TITLE), self::F_TITLE);
		$te->setRequired(true);
		$this->addItem($te);

		$te = new ilTextAreaInputGUI($this->parent_gui->txt(self::F_DESCRIPTION), self::F_DESCRIPTION);
		$this->addItem($te);

		$te = new ilTextInputGUI($this->parent_gui->txt(self::F_PUBLICATION_ID), self::F_PUBLICATION_ID);
		$te->setRequired(true);
		$this->addItem($te);

		$te = new ilSelectInputGUI($this->parent_gui->txt(self::F_MD_TYPE), self::F_MD_TYPE);
		$te->setRequired(true);
		$te->setOptions(array(
			NULL => $this->parent_gui->txt('md_type_select'),
			xoctPublicationUsage::MD_TYPE_ATTACHMENT => $this->parent_gui->txt('md_type_' . xoctPublicationUsage::MD_TYPE_ATTACHMENT),
			xoctPublicationUsage::MD_TYPE_MEDIA => $this->parent_gui->txt('md_type_' . xoctPublicationUsage::MD_TYPE_MEDIA)
		));
		$this->addItem($te);

		$te = new ilTextInputGUI($this->parent_gui->txt(self::F_EXT_ID), self::F_EXT_ID);
		$te->setRequired(true);
		$this->addItem($te);
	}


	public function fillForm() {
		$array = array(
			self::F_USAGE_ID => $this->object->getUsageId(),
			self::F_TITLE => $this->object->getTitle(),
			self::F_DESCRIPTION => $this->object->getDescription(),
			self::F_PUBLICATION_ID => $this->object->getPublicationId(),
			self::F_EXT_ID => $this->object->getExtId(),
			self::F_MD_TYPE => $this->object->getMdType(),
		);

		$this->setValuesByArray($array);
	}


	/**
	 * returns whether checkinput was successful or not.
	 *
	 * @return bool
	 */
	public function fillObject() {
		if (! $this->checkInput()) {
			return false;
		}

		$this->object->setUsageId($this->getInput(self::F_USAGE_ID));
		$this->object->setTitle($this->getInput(self::F_TITLE));
		$this->object->setDescription($this->getInput(self::F_DESCRIPTION));
		$this->object->setPublicationId($this->getInput(self::F_PUBLICATION_ID));
		$this->object->setExtId($this->getInput(self::F_EXT_ID));
		$this->object->setMdType($this->getInput(self::F_MD_TYPE));

		return true;
	}


	/**
	 * @return bool|string
	 */
	public function saveObject() {
		if (! $this->fillObject()) {
			return false;
		}
		if (! xoctPublicationUsage::where(array( 'usage_id' => $this->object->getUsageId() ))->hasSets()) {
			$this->object->create();
		} else {
			$this->object->update();
		}

		return true;
	}


	protected function initButtons() {
		if ($this->is_new) {
			$this->setTitle($this->parent_gui->txt('create'));
			$this->addCommandButton(xoctPublicationUsageGUI::CMD_CREATE, $this->parent_gui->txt(xoctPublicationUsageGUI::CMD_CREATE));
		} else {
			$this->setTitle($this->parent_gui->txt('edit'));
			$this->addCommandButton(xoctPublicationUsageGUI::CMD_UPDATE, $this->parent_gui->txt(xoctPublicationUsageGUI::CMD_UPDATE));
		}

		$this->addCommandButton(xoctPublicationUsageGUI::CMD_CANCEL, $this->parent_gui->txt(xoctPublicationUsageGUI::CMD_CANCEL));
	}
}

?>
