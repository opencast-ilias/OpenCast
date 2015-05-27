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

	const F_DOMAIN = 'domain';
	const F_EXT_ID = 'ext_id';
	const F_STATUS = 'status';
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
	 * @param                   $parent_gui
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
		$this->is_new = ($this->object->getDomain() == '');

		//xoctWaiterGUI::init();
		$this->initForm();
	}


	protected function initForm() {
		$this->setTarget('_top');
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		$this->initButtons();

		$te = new ilTextInputGUI($this->txt(self::F_DOMAIN), self::F_DOMAIN);
		$te->setRequired(true);
		$te->setDisabled(!$this->is_new);
		$this->addItem($te);

		$te = new ilTextInputGUI($this->txt(self::F_EXT_ID), self::F_EXT_ID);
		$te->setRequired(true);
		$this->addItem($te);
	}


	public function fillForm() {
		$array = array(
			self::F_DOMAIN => $this->object->getDomain(),
			self::F_EXT_ID => $this->object->getExtId(),
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

		$this->object->setDomain($this->getInput(self::F_DOMAIN));
		$this->object->setExtId($this->getInput(self::F_EXT_ID));

		return true;
	}


	/**
	 * @param $key
	 *
	 * @return string
	 */
	protected function txt($key) {
		return $this->pl->txt('system_account_' . $key);
	}


	/**
	 * @param $key
	 *
	 * @return string
	 */
	protected function infoTxt($key) {
		return $this->pl->txt('system_account_' . $key . '_info');
	}


	/**
	 * @return bool|string
	 */
	public function saveObject() {
		if (! $this->fillObject()) {
			return false;
		}
		if (! xoctPublicationUsage::where(array( 'domain' => $this->object->getDomain() ))->hasSets()) {
			$this->object->create();
		} else {
			$this->object->update();
		}

		return true;
	}


	protected function initButtons() {
		if ($this->is_new) {
			$this->setTitle($this->txt('create'));
			$this->addCommandButton(xoctPublicationUsageGUI::CMD_CREATE, $this->txt(xoctPublicationUsageGUI::CMD_CREATE));
		} else {
			$this->setTitle($this->txt('edit'));
			$this->addCommandButton(xoctPublicationUsageGUI::CMD_UPDATE, $this->txt(xoctPublicationUsageGUI::CMD_UPDATE));
		}

		$this->addCommandButton(xoctPublicationUsageGUI::CMD_CANCEL, $this->txt(xoctPublicationUsageGUI::CMD_CANCEL));
	}


	/**
	 * Workaround for returning an object of class ilPropertyFormGUI instead of this subclass
	 * this is used, until bug (http://ilias.de/mantis/view.php?id=13168) is fixed
	 *
	 * @return ilPropertyFormGUI This object but as an ilPropertyFormGUI instead of a xdglRequestFormGUI
	 */
	public function getAsPropertyFormGui() {
		$ilPropertyFormGUI = new ilPropertyFormGUI();
		$ilPropertyFormGUI->setFormAction($this->getFormAction());
		$ilPropertyFormGUI->setTitle($this->getTitle());

		$ilPropertyFormGUI->addCommandButton(xoctSeriesGUI::CMD_SAVE, $this->lng->txt(xoctSeriesGUI::CMD_SAVE));
		$ilPropertyFormGUI->addCommandButton(xoctSeriesGUI::CMD_CANCEL, $this->lng->txt(xoctSeriesGUI::CMD_CANCEL));
		foreach ($this->getItems() as $item) {
			$ilPropertyFormGUI->addItem($item);
		}

		return $ilPropertyFormGUI;
	}
	//
	//
	//	public function addToInfoScreen(ilInfoScreenGUI $ilInfoScreenGUI) {
	//	}
	//
	//
	protected function initView() {
		$this->initForm();
		/**
		 * @var $item ilNonEditableValueGUI
		 */
		foreach ($this->getItems() as $item) {
			$te = new ilNonEditableValueGUI($this->txt($item->getPostVar()), $item->getPostVar());
			$this->removeItemByPostVar($item->getPostVar());
			$this->addItem($te);
		}
	}


	protected static $disciplines = array(
		1932 => 'Arts & Culture',
		5314 => 'Architecture',
		6302 => 'Landscape architecture',
		5575 => 'Spatial planning',
		9202 => 'Art history',
		3119 => 'Design',
		6095 => 'Industrial design',
		5103 => 'Visual communication',
		5395 => 'Film',
		8202 => 'Music',
		2043 => 'Music education',
		9610 => 'School and church music',
		3829 => 'Theatre',
		1497 => 'Visual arts',
		6950 => 'Business',
		1676 => 'Business Administration',
		4949 => 'Business Informatics',
		7290 => 'Economics',
		2108 => 'Facility Management',
		7641 => 'Hotel business',
		6238 => 'Tourism',
		5214 => 'Education',
		1672 => 'Logopedics',
		1406 => 'Pedagogy',
		3822 => 'Orthopedagogy',
		2150 => 'Special education',
		9955 => 'Teacher education',
		6409 => 'Primary school',
		7008 => 'Secondary school I',
		4233 => 'Secondary school II',
		8220 => 'Health',
		2075 => 'Dentistry',
		5955 => 'Human medicine',
		5516 => 'Nursing',
		3424 => 'Pharmacy',
		4864 => 'Therapy',
		6688 => 'Occupational therapy',
		7072 => 'Physiotherapy',
		3787 => 'Veterinary medicine',
		4832 => 'Humanities',
		1438 => 'Archeology',
		8796 => 'History',
		7210 => 'Linguistics & Literature (LL)',
		9557 => 'Classical European languages',
		9391 => 'English LL',
		9472 => 'French LL',
		4391 => 'German LL',
		3468 => 'Italian LL',
		7408 => 'Linguistics',
		6230 => 'Other modern European languages',
		5676 => 'Other non-European languages',
		5424 => 'Rhaeto-Romanic LL',
		7599 => 'Translation studies',
		7258 => 'Musicology',
		4761 => 'Philosophy',
		3867 => 'Theology',
		6527 => 'General theology',
		5633 => 'Protestant theology',
		9787 => 'Roman catholic theology',
		5889 => 'Interdisciplinary & Other',
		6059 => 'Information & documentation',
		5561 => 'Military sciences',
		8683 => 'Sport',
		1861 => 'Law',
		4890 => 'Business law',
		2990 => 'Natural sciences & Mathematics',
		8990 => 'Astronomy',
		4195 => 'Biology',
		7793 => 'Ecology',
		6451 => 'Chemistry',
		1266 => 'Computer science',
		5255 => 'Earth Sciences',
		7950 => 'Geography',
		2158 => 'Mathematics',
		6986 => 'Physics',
		8637 => 'Social sciences',
		9619 => 'Communication and media studies',
		8367 => 'Ethnology',
		1774 => 'Gender studies',
		1514 => 'Political science',
		6005 => 'Psychology',
		7288 => 'Social work',
		6525 => 'Sociology',
		9321 => 'Technology & Applied sciences',
		3624 => 'Agriculture',
		1442 => 'Enology',
		1892 => 'Biotechnology',
		7132 => 'Building Engineering',
		5727 => 'Chemical Engineering',
		9389 => 'Construction Science',
		2527 => 'Civil Engineering',
		9738 => 'Rural Engineering and Surveying',
		5742 => 'Electrical Engineering',
		2850 => 'Environmental Engineering',
		9768 => 'Food technology',
		2979 => 'Forestry',
		1566 => 'Material sciences',
		8189 => 'Mechanical Engineering',
		5324 => 'Automoive Engineering',
		8502 => 'Microtechnology',
		4380 => 'Production and Enterprise',
		7303 => 'Telecommunication',
	);
}

?>
