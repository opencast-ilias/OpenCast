<?php
use srag\DIC\OpencastObject\DICTrait;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;

/**
 * Class xoctPublicationUsageFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctPublicationUsageFormGUI extends ilPropertyFormGUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpencastObjectPlugin::class;

	const F_USAGE_ID = 'usage_id';
	const F_TITLE = 'title';
	const F_DESCRIPTION = 'description';
	const F_CHANNEL = 'channel';
	const F_STATUS = 'status';
	const F_SEARCH_KEY = 'search_key';
	const F_FLAVOR = PublicationUsage::SEARCH_KEY_FLAVOR;
	const F_TAG = PublicationUsage::SEARCH_KEY_TAG;
	const F_MD_TYPE = 'md_type';
	const F_ALLOW_MULTIPLE = 'allow_multiple';

	/**
	 * @var  PublicationUsage
	 */
	protected $object;
	/**
	 * @var xoctPublicationUsageGUI
	 */
	protected $parent_gui;


	/**
	 * @param xoctPublicationUsageGUI $parent_gui
	 * @param PublicationUsage        $xoctPublicationUsage
	 */
	public function __construct($parent_gui, $xoctPublicationUsage) {
		global $DIC;
		$DIC->ui()->mainTemplate()->addJavaScript(ilOpencastObjectPlugin::getInstance()->getDirectory() . '/templates/default/publication_usage_form.min.js');
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
            PublicationUsage::MD_TYPE_PUBLICATION_ITSELF => $this->parent_gui->txt('md_type_' . PublicationUsage::MD_TYPE_PUBLICATION_ITSELF),
            PublicationUsage::MD_TYPE_ATTACHMENT         => $this->parent_gui->txt('md_type_' . PublicationUsage::MD_TYPE_ATTACHMENT),
            PublicationUsage::MD_TYPE_MEDIA              => $this->parent_gui->txt('md_type_' . PublicationUsage::MD_TYPE_MEDIA)
		));
		$this->addItem($te);

		$radio = new ilRadioGroupInputGUI($this->parent_gui->txt(self::F_SEARCH_KEY), self::F_SEARCH_KEY);
		$radio->setInfo($this->parent_gui->txt(self::F_SEARCH_KEY . '_info'));

		$opt = new ilRadioOption($this->parent_gui->txt(self::F_FLAVOR), self::F_FLAVOR);
		$te = new ilTextInputGUI('', self::F_FLAVOR);
		$te->setInfo($this->parent_gui->txt(self::F_FLAVOR . '_info'));
		$opt->addSubItem($te);
		$radio->addOption($opt);

		$opt = new ilRadioOption($this->parent_gui->txt(self::F_TAG), self::F_TAG);
		$te = new ilTextInputGUI('', self::F_TAG);
		$opt->addSubItem($te);
		$radio->addOption($opt);

		$radio->setValue(self::F_FLAVOR);
		$this->addItem($radio);

		if (in_array($this->object->getUsageId(), [PublicationUsage::USAGE_DOWNLOAD, PublicationUsage::USAGE_DOWNLOAD_FALLBACK])) {
			$allow_multiple = new ilCheckboxInputGUI($this->parent_gui->txt(self::F_ALLOW_MULTIPLE), self::F_ALLOW_MULTIPLE);
		} else {
			$allow_multiple = new ilHiddenInputGUI(self::F_ALLOW_MULTIPLE);
			$allow_multiple->setValue(0);
		}
		$this->addItem($allow_multiple);
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
			self::F_SEARCH_KEY => $this->object->getSearchKey(),
			self::F_FLAVOR => $this->object->getFlavor(),
			self::F_TAG => $this->object->getTag(),
			self::F_MD_TYPE => $this->object->getMdType(),
			self::F_ALLOW_MULTIPLE => $this->object->isAllowMultiple(),
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
		$this->object->setSearchKey($this->getInput(self::F_SEARCH_KEY));
		$this->object->setFlavor($this->getInput(self::F_FLAVOR));
		$this->object->setTag($this->getInput(self::F_TAG));
		$this->object->setMdType($this->getInput(self::F_MD_TYPE));
		$this->object->setAllowMultiple((bool)$this->getInput(self::F_ALLOW_MULTIPLE));

		return true;
	}


	/**
	 * @return bool|string
	 */
	public function saveObject() {
		if (! $this->fillObject()) {
			return false;
		}
		if (! PublicationUsage::where(array('usage_id' => $this->object->getUsageId() ))->hasSets()) {
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
