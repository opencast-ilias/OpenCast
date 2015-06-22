<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');

/**
 * Class xoctEventFormGUI
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctEventFormGUI extends ilPropertyFormGUI {

	const F_TITLE = 'title';
	const F_DESCRIPTION = 'description';
	const F_FILE_PRESENTER = 'file_presenter';
	/**
	 * @var  xoctEvent
	 */
	protected $object;
	/**
	 * @var xoctEventGUI
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
	 * @var bool
	 */
	protected $external = true;


	/**
	 *
	 */
	public function __construct($parent_gui, xoctEvent $object, xoctOpenCast $xoctOpenCast, $view = false, $infopage = false, $external = true) {
		global $ilCtrl, $lng, $tpl;
		$this->object = $object;
		$this->xoctOpenCast = $xoctOpenCast;
		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->pl = ilOpenCastPlugin::getInstance();
		$this->ctrl->saveParameter($parent_gui, xoctEventGUI::IDENTIFIER);
		$this->lng = $lng;
		$this->is_new = ($this->object->getIdentifier() == '');
		$this->view = $view;
		$this->infopage = $infopage;
		$this->external = $external;
		xoctWaiterGUI::init();

		if ($view) {
			$this->initView();
		} else {
			$this->initForm();
		}
	}


	protected function initForm() {
		$this->setTarget('_top');
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		$this->initButtons();

		$te = new ilTextInputGUI($this->txt(self::F_TITLE), self::F_TITLE);
		$te->setRequired(true);
		$this->addItem($te);

		if ($this->is_new) {
			$te = new ilFileInputGUI($this->txt(self::F_FILE_PRESENTER), self::F_FILE_PRESENTER);
			$te->setRequired(true);
			$this->addItem($te);
		}

		$te = new ilTextAreaInputGUI($this->txt(self::F_DESCRIPTION), self::F_DESCRIPTION);
		$this->addItem($te);
	}


	public function fillForm() {
		$array = array(
			self::F_TITLE => $this->object->getTitle(),
			self::F_DESCRIPTION => $this->object->getDescription(),
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
		$this->object->setTitle($this->getInput(self::F_TITLE));
		$this->object->setDescription($this->getInput(self::F_DESCRIPTION));

		return true;
	}


	/**
	 * @param $key
	 *
	 * @return string
	 */
	protected function txt($key) {
		return $this->parent_gui->txt($key);
	}


	/**
	 * @param $key
	 *
	 * @return string
	 */
	protected function infoTxt($key) {
		return $this->pl->txt('event_' . $key . '_info');
	}


	/**
	 * @return bool|string
	 */
	public function saveObject() {
		if (! $this->fillObject()) {
			return false;
		}
		if ($this->object->getIdentifier()) {
			$this->object->update();
		} else {
			$this->object->setSeriesIdentifier($this->xoctOpenCast->getSeriesIdentifier());
			$this->object->create($_FILES[self::F_FILE_PRESENTER]);
		}

		return $this->object->getIdentifier();
	}


	protected function initButtons() {
		if ($this->is_new) {
			$this->setTitle($this->txt('create'));
			$this->addCommandButton(xoctEventGUI::CMD_CREATE, $this->txt(xoctEventGUI::CMD_CREATE));
		} else {
			$this->setTitle($this->txt('edit'));
			$this->addCommandButton(xoctEventGUI::CMD_UPDATE, $this->txt(xoctEventGUI::CMD_UPDATE));
		}

		$this->addCommandButton(xoctEventGUI::CMD_CANCEL, $this->txt(xoctEventGUI::CMD_CANCEL));
	}


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
}


