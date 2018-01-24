<?php
/**
 * Class xoctSystemAccountFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctSystemAccountFormGUI extends ilPropertyFormGUI {

	const F_DOMAIN = 'domain';
	const F_EXT_ID = 'ext_id';
	const F_STATUS = 'status';
	/**
	 * @var  xoctSystemAccount
	 */
	protected $object;
	/**
	 * @var xoctSystemAccountGUI
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
	 * @param xoctSystemAccount $xoctSystemAccount
	 */
	public function __construct($parent_gui, xoctSystemAccount $xoctSystemAccount) {
		global $ilCtrl, $lng, $tpl;
		$this->object = $xoctSystemAccount;
		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->pl = ilOpenCastPlugin::getInstance();
		$this->ctrl->saveParameter($parent_gui, xoctSystemAccountGUI::IDENTIFIER);
		$this->lng = $lng;
		$this->is_new = ($this->object->getDomain() == '');

		//xoctWaiterGUI::init();
		$this->initForm();
	}


	protected function initForm() {
		$this->setTarget('_top');
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		$this->initButtons();

		$te = new ilTextInputGUI($this->parent_gui->txt(self::F_DOMAIN), self::F_DOMAIN);
		$te->setRequired(true);
		$te->setDisabled(! $this->is_new);
		$this->addItem($te);

		$te = new ilTextInputGUI($this->parent_gui->txt(self::F_EXT_ID), self::F_EXT_ID);
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
		if (! xoctSystemAccount::where(array( 'domain' => $this->object->getDomain() ))->hasSets()) {
			$this->object->create();
		} else {
			$this->object->update();
		}

		return true;
	}


	protected function initButtons() {
		if ($this->is_new) {
			$this->setTitle($this->parent_gui->txt('create'));
			$this->addCommandButton(xoctSystemAccountGUI::CMD_CREATE, $this->parent_gui->txt(xoctSystemAccountGUI::CMD_CREATE));
		} else {
			$this->setTitle($this->parent_gui->txt('edit'));
			$this->addCommandButton(xoctSystemAccountGUI::CMD_UPDATE, $this->parent_gui->txt(xoctSystemAccountGUI::CMD_UPDATE));
		}

		$this->addCommandButton(xoctSystemAccountGUI::CMD_CANCEL, $this->parent_gui->txt(xoctSystemAccountGUI::CMD_CANCEL));
	}
}

?>
