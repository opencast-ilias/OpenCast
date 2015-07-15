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
	const F_FILE_PRESENTATION = 'file_presenter';
	const F_IDENTIFIER = 'identifier';
	const F_CREATOR = 'creator';
	const F_DURATION = 'duration';
	const F_PROCESSING_STATE = 'processing_state';
	const F_START_TIME = 'start_time';
	const F_LOCATION = 'location';
	const F_PRESENTERS = 'presenters';
	const F_CREATED = 'created';
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
		$te->setRequired(true);
		$this->addItem($te);
	}


	public function fillForm() {
		$array = array(
			self::F_TITLE => $this->object->getTitle(),
			self::F_DESCRIPTION => $this->object->getDescription(),
			self::F_IDENTIFIER => $this->object->getIdentifier(),
			self::F_CREATOR => $this->object->getCreator(),
			self::F_CREATED => $this->object->getCreated(),
			self::F_DURATION => $this->object->getDuration(),
			self::F_PROCESSING_STATE => $this->object->getProcessingState(),
			self::F_START_TIME => $this->object->getStartTime(),
			self::F_LOCATION => $this->object->getLocation(),
			self::F_PRESENTERS => $this->object->getPresenters(),
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
			$this->object->create();
		}

		return $this->object->getIdentifier();
	}


	protected function initButtons() {
		switch (true) {
			case  $this->is_new AND ! $this->view:
				$this->setTitle($this->txt('create'));
				$this->addCommandButton(xoctEventGUI::CMD_CREATE, $this->txt(xoctEventGUI::CMD_CREATE));
				$this->addCommandButton(xoctEventGUI::CMD_CANCEL, $this->txt(xoctEventGUI::CMD_CANCEL));
				break;
			case  ! $this->is_new AND ! $this->view:
				$this->setTitle($this->txt('edit'));
				$this->addCommandButton(xoctEventGUI::CMD_UPDATE, $this->txt(xoctEventGUI::CMD_UPDATE));
				$this->addCommandButton(xoctEventGUI::CMD_CANCEL, $this->txt(xoctEventGUI::CMD_CANCEL));
				break;
			case $this->view:
				$this->setTitle($this->txt('view'));
				$this->addCommandButton(xoctEventGUI::CMD_CANCEL, $this->txt(xoctEventGUI::CMD_CANCEL));
				break;
		}
	}


	protected function initView() {
		$this->initForm();

		$te = new ilNonEditableValueGUI($this->txt(self::F_IDENTIFIER), self::F_IDENTIFIER);
		$this->addItem($te);

		$te = new ilNonEditableValueGUI($this->txt(self::F_CREATOR), self::F_CREATOR);
		$this->addItem($te);

		$te = new ilNonEditableValueGUI($this->txt(self::F_CREATED), self::F_CREATED);
		$this->addItem($te);

		$te = new ilNonEditableValueGUI($this->txt(self::F_DURATION), self::F_DURATION);
		$this->addItem($te);

		$te = new ilNonEditableValueGUI($this->txt(self::F_PROCESSING_STATE), self::F_PROCESSING_STATE);
		$this->addItem($te);

		$te = new ilNonEditableValueGUI($this->txt(self::F_START_TIME), self::F_START_TIME);
		$this->addItem($te);

		$te = new ilNonEditableValueGUI($this->txt(self::F_LOCATION), self::F_LOCATION);
		$this->addItem($te);

		$te = new ilNonEditableValueGUI($this->txt(self::F_PRESENTERS), self::F_PRESENTERS);
		$this->addItem($te);

		/**
		 * @var $item ilNonEditableValueGUI
		 */
		foreach ($this->getItems() as $item) {
			$te = new ilNonEditableValueGUI($this->txt($item->getPostVar()), $item->getPostVar());
			$this->removeItemByPostVar($item->getPostVar());
			$this->addItem($te);
		}
		$te = new ilCustomInputGUI('detail', 'detail');
		$te->setHtml('<table><tr><td>' . $this->object->__toCsv("</td><td>", "</td></tr><tr><td>") . '</td></tr></table>');
		$this->addItem($te);
	}


	/**
	 * @return xoctEvent
	 */
	public function getObject() {
		return $this->object;
	}


	/**
	 * @param xoctEvent $object
	 */
	public function setObject($object) {
		$this->object = $object;
	}
}


