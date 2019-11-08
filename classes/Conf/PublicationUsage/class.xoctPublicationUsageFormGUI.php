<?php
use srag\DIC\OpenCast\DICTrait;
/**
 * Class xoctPublicationUsageFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctPublicationUsageFormGUI extends ilPropertyFormGUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

	const F_USAGE_ID = 'usage_id';
	const F_TITLE = 'title';
	const F_DESCRIPTION = 'description';
	const F_CHANNEL = 'channel';
	const F_STATUS = 'status';
	const F_FLAVOR = 'flavor';
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
	 * @param xoctPublicationUsageGUI $parent_gui
	 * @param xoctPublicationUsage $xoctPublicationUsage
	 */
	public function __construct($parent_gui, $xoctPublicationUsage) {
		parent::__construct();
		$this->object = $xoctPublicationUsage;
		$this->parent_gui = $parent_gui;
		self::dic()->ctrl()->saveParameter($parent_gui, xoctPublicationUsageGUI::IDENTIFIER);
		$this->is_new = ($this->object->getUsageId() == '');
		$this->initForm();
	}


	/**
	 *
	 */
	protected function initForm() {
		$this->setTarget('_top');
		$this->setFormAction(self::dic()->ctrl()->getFormAction($this->parent_gui));
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

		$te = new ilTextInputGUI($this->parent_gui->txt(self::F_CHANNEL), self::F_CHANNEL);
		$te->setRequired(true);
		$this->addItem($te);

		$te = new ilSelectInputGUI($this->parent_gui->txt(self::F_MD_TYPE), self::F_MD_TYPE);
		$te->setRequired(true);
		$te->setOptions(array(
			xoctPublicationUsage::MD_TYPE_PUBLICATION_ITSELF => $this->parent_gui->txt('md_type_' . xoctPublicationUsage::MD_TYPE_PUBLICATION_ITSELF),
			xoctPublicationUsage::MD_TYPE_ATTACHMENT => $this->parent_gui->txt('md_type_' . xoctPublicationUsage::MD_TYPE_ATTACHMENT),
			xoctPublicationUsage::MD_TYPE_MEDIA => $this->parent_gui->txt('md_type_' . xoctPublicationUsage::MD_TYPE_MEDIA)
		));
		$this->addItem($te);

		$te = new ilTextInputGUI($this->parent_gui->txt(self::F_FLAVOR), self::F_FLAVOR);
		$this->addItem($te);
	}


	/**
	 *
	 */
	public function fillForm() {
		$array = array(
			self::F_USAGE_ID => $this->object->getUsageId(),
			self::F_TITLE => $this->object->getTitle(),
			self::F_DESCRIPTION => $this->object->getDescription(),
			self::F_CHANNEL => $this->object->getChannel(),
			self::F_FLAVOR => $this->object->getFlavor(),
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
		$this->object->setChannel($this->getInput(self::F_CHANNEL));
		$this->object->setFlavor($this->getInput(self::F_FLAVOR));
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


	/**
	 *
	 */
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
