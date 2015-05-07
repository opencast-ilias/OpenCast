<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
/**
 * Class xoctSeriesFormGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctSeriesFormGUI extends ilPropertyFormGUI{
	const F_COURSE_NAME = 'course_name';

	/**
	 * @var  xoctSeries
	 */
	protected $object;
	/**
	 * @var xoctSeriesGUI
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
	 * @param            $parent_gui
	 * @param xoctSeries $object
	 * @param bool       $view
	 * @param bool       $infopage
	 * @param bool       $external
	 */
	public function __construct($parent_gui, xoctSeries $object, $view = false, $infopage = false, $external = true) {
		global $ilCtrl, $lng;
		$this->object = $object;
		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->pl = ilOpenCastPlugin::getInstance();
		if ($_GET['rl'] == 'true') {
			$this->pl->updateLanguageFiles();
		}
		$this->ctrl->saveParameter($parent_gui, xoctSeriesGUI::SERIES_ID);
		$this->ctrl->saveParameter($parent_gui, 'new_type');
		$this->lng = $lng;
		$this->is_new = ($this->object->getId() == 0);
		$this->view = $view;
		$this->infopage = $infopage;
		$this->external = $external;
		if ($view) {
			$this->initView();
		} else {
			$this->initForm();
		}
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

		$ilPropertyFormGUI->addCommandButton(xdglRequestGUI::CMD_SAVE, $this->lng->txt(xdglRequestGUI::CMD_SAVE));
		$ilPropertyFormGUI->addCommandButton('cancel', $this->lng->txt('cancel'));
		foreach ($this->getItems() as $item) {
			$ilPropertyFormGUI->addItem($item);
		}

		return $ilPropertyFormGUI;
	}


	public function addToInfoScreen(ilInfoScreenGUI $ilInfoScreenGUI) {
	}


	protected function initView() {
		if (!$this->infopage) {
			$te = new ilNonEditableValueGUI($this->txt(self::F_REQUESTER_FULLNAME), self::F_REQUESTER_FULLNAME);
			$this->addItem($te);

			$te = new ilNonEditableValueGUI($this->txt(self::F_REQUESTER_MAILTO), self::F_REQUESTER_MAILTO);
			$this->addItem($te);

			$te = new ilNonEditableValueGUI($this->txt(self::F_CREATE_DATE), self::F_CREATE_DATE);
			$this->addItem($te);

			$te = new ilNonEditableValueGUI($this->txt(self::F_LAST_STATUS_CHANGE), self::F_LAST_STATUS_CHANGE);
			$this->addItem($te);

			$te = new ilNonEditableValueGUI($this->txt(self::F_MODIFIED_BY), self::F_MODIFIED_BY);
			$this->addItem($te);
		}

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


	protected function initForm() {
		$this->setTarget('_top');
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		$this->initButtons();

		// Anzahl DigiLits
		$te = new ilNonEditableValueGUI($this->txt(self::F_COUNT), self::F_COUNT);
		$this->addItem($te);

		// Course ID
		if ($this->is_new) {
			$te = new ilHiddenInputGUI(self::F_CRS_REF_ID);
			$this->addItem($te);
		}

		// Course Name
		$course_name = new ilTextInputGUI($this->txt(self::F_COURSE_NAME), self::F_COURSE_NAME);
		if ($this->is_new) {
			$course_name->setDisabled(true);
		}
		//		$course_name->setRequired(true);
		$this->addItem($course_name);

		// Author
		$bj = new ilTextInputGUI($this->txt(self::F_AUTHOR), self::F_AUTHOR);
		$bj->setRequired(true);
		$this->addItem($bj);

		//Add input for title and set value storred in session by createObject
		$ti = new ilTextInputGUI($this->txt(self::F_TITLE), self::F_TITLE);
		$ti->setRequired(true);
		$this->addItem($ti);

		// in book/journal
		$bj = new ilTextInputGUI($this->txt(self::F_BOOK), self::F_BOOK);
		$bj->setRequired(true);
		$this->addItem($bj);

		// editor
		$pu = new ilTextInputGUI($this->txt(self::F_EDITOR), self::F_EDITOR);
		$this->addItem($pu);

		// place of publication
		$pp = new ilTextInputGUI($this->txt(self::F_LOCATION), self::F_LOCATION);
		$this->addItem($pp);

		// publishing_company
		$pc = new ilTextInputGUI($this->txt(self::F_PUBLISHER), self::F_PUBLISHER);
		$this->addItem($pc);

		// publishing year
		$ye = new ilTextInputGUI($this->txt(self::F_PUBLISHING_YEAR), self::F_PUBLISHING_YEAR);
		$ye->setMaxLength(4);
		$ye->setRequired(true);
		//Set Regex Check: must be 4 digits
		$ye->setValidationRegexp(self::REGEX_FOUR_DIGITS_ONLY);
		$ye->setValidationFailureMessage($this->pl->txt('validation_failure_4_digits_required'));
		$this->addItem($ye);

		// pages
		$pa = new ilTextInputGUI($this->txt(self::F_PAGES), self::F_PAGES);
		$pa->setRequired(true);
		$this->addItem($pa);

		// volume (Band)
		$vo = new ilTextInputGUI($this->txt(self::F_VOLUME_YEAR), self::F_VOLUME_YEAR);
		$this->addItem($vo);

		// nur diese Auflage
		$na = new ilCheckboxInputGUI($this->txt(self::F_EDITION_RELEVANT), self::F_EDITION_RELEVANT);
		$this->addItem($na);

		// ISSN number
		$in = new ilTextInputGUI($this->txt(self::F_ISSN), self::F_ISSN);
		$this->addItem($in);

		//  Notice
		$in = new ilTextAreaInputGUI($this->txt(self::F_NOTICE), self::F_NOTICE);
		$in->setCols(40);
		$in->setRows(6);
		$this->addItem($in);

		// Internal Notice
		if (!$this->external) {
			$in = new ilTextAreaInputGUI($this->txt(self::F_INTERNAL_NOTICE), self::F_INTERNAL_NOTICE);
			$this->addItem($in);
		}

		// EULA
		if ($this->is_new) {
			$eula = new ilCheckboxInputGUI($this->txt(self::F_CONFIRM_EULA), self::F_CONFIRM_EULA);
			$eula->setOptionTitle($this->txt(self::F_CONFIRM_EULA . '_title'));
			$eula->setRequired(true);
			$tpl = $this->pl->getTemplate('default/tpl.eula.html');
			$tpl->setVariable('TXT_SHOW', $this->txt(self::F_CONFIRM_EULA . '_show'));
			$tpl->setVariable('EULA', xdglConfig::get(xdglConfig::F_EULA_TEXT));

			$eula->setInfo($tpl->get());
			$this->addItem($eula);
		}
	}


	/**
	 * @param null $ref_id
	 */
	public function fillFormRandomized($ref_id = NULL) {
		if ($ref_id) {
			$this->object->setCrsRefId($ref_id);
		}
		$array = array(
			self::F_AUTHOR => 'Author Name',
			self::F_TITLE => 'Article Name',
			self::F_BOOK => 'The Book',
			self::F_EDITOR => '',
			self::F_LOCATION => 'Berne',
			self::F_PUBLISHER => 'Publisher Name',
			self::F_PUBLISHING_YEAR => 2004,
			self::F_PAGES => '50-89',
			self::F_EDITION_RELEVANT => false,
			self::F_ISSN => '',
			self::F_VOLUME_YEAR => 2004,
			self::F_NOTICE => 'This Text only!',
			self::F_COURSE_NAME => $this->object->getCourseTitle(),

		);
		$this->setValuesByArray($array);
	}


	/**
	 * @param int $ref_id
	 */
	public function fillForm($ref_id = NULL) {
		if ($ref_id) {
			$this->object->setCrsRefId($ref_id);
		}
		$ilObjUserRequester = new ilObjUser($this->object->getRequesterUsrId());
		$ilObjUserModified = new ilObjUser($this->object->getLastModifiedByUsrId());

		$array = array(
			self::F_AUTHOR => $this->object->getAuthor(),
			self::F_TITLE => $this->object->getTitle(),
			self::F_BOOK => $this->object->getBook(),
			self::F_EDITOR => $this->object->getEditor(),
			self::F_LOCATION => $this->object->getLocation(),
			self::F_PUBLISHER => $this->object->getPublisher(),
			self::F_PUBLISHING_YEAR => $this->object->getPublishingYear(),
			self::F_PAGES => $this->object->getPages(),
			self::F_EDITION_RELEVANT => $this->object->getEditionRelevant(),
			self::F_ISSN => $this->object->getIssn(),
			self::F_COUNT => $this->object->getAmoutOfDigiLitsInCourse() . '/' . xdglConfig::get(xdglConfig::F_MAX_DIGILITS),
			self::F_CRS_REF_ID => $this->object->getCrsRefId(),
			self::F_REQUESTER_FULLNAME => $ilObjUserRequester->getPresentationTitle(),
			self::F_REQUESTER_MAILTO => $ilObjUserRequester->getEmail(),
			self::F_CREATE_DATE => date('d.m.Y - H:i:s', $this->object->getCreateDate()),
			self::F_LAST_STATUS_CHANGE => date('d.m.Y - H:i:s', $this->object->getDateLastStatusChange()),
			self::F_MODIFIED_BY => $ilObjUserModified->getPresentationTitle(),
			self::F_VOLUME_YEAR => $this->object->getVolume(),
			self::F_NOTICE => $this->object->getNotice(),
			self::F_INTERNAL_NOTICE => $this->object->getInternalNotice(),
			self::F_COURSE_NAME => $this->object->getCourseTitle(),
		);
		if ($this->is_new) {
			$array[self::F_COUNT] = $this->object->getAmoutOfDigiLitsInCourse() + 1 . '/' . xdglConfig::get(xdglConfig::F_MAX_DIGILITS);
		}
		if ($this->view) {
			$array[self::F_EDITION_RELEVANT] = xdglRequest::boolTextRepresentation($this->object->getEditionRelevant());
		} else {
			$array[self::F_EDITION_RELEVANT] = $this->object->getEditionRelevant();
		}

		$this->setValuesByArray($array);
	}


	/**
	 * returns whether checkinput was successful or not.
	 *
	 * @return bool
	 */
	public function fillObject($ref_id) {
		if (!$this->checkInput()) {
			return false;
		}
		if ($this->is_new AND !$this->getInput(self::F_CONFIRM_EULA)) {
			/**
			 * @var $item ilCheckboxInputGUI
			 */
			$item = $this->getItemByPostVar(self::F_CONFIRM_EULA);
			$item->setAlert($this->txt(self::F_CONFIRM_EULA . '_warning'));

			return false;
		}
		$this->object->setCourseNumber($this->getInput(self::F_COURSE_NAME));
		$this->object->setAuthor($this->getInput(self::F_AUTHOR));
		$this->object->setTitle($this->getInput(self::F_TITLE));
		$this->object->setBook($this->getInput(self::F_BOOK));
		$this->object->setEditor($this->getInput(self::F_EDITOR));
		$this->object->setLocation($this->getInput(self::F_LOCATION));
		$this->object->setPublisher($this->getInput(self::F_PUBLISHER));
		$this->object->setPublishingYear($this->getInput(self::F_PUBLISHING_YEAR));
		$this->object->setPages($this->getInput(self::F_PAGES));
		$this->object->setNotice($this->getInput(self::F_NOTICE));
		if (!$this->external) {
			$this->object->setInternalNotice($this->getInput(self::F_INTERNAL_NOTICE));
		}
		if ($this->getInput(self::F_VOLUME_YEAR) === '') {
			$this->object->setVolume(NULL);
		} else {
			$this->object->setVolume($this->getInput(self::F_VOLUME_YEAR));
		}
		$this->object->setEditionRelevant($this->getInput(self::F_EDITION_RELEVANT));
		$this->object->setIssn($this->getInput(self::F_ISSN));

		if ($this->is_new AND $ref_id) {
			$this->object->setCrsRefId($ref_id);
		}

		return true;
	}


	/**
	 * @param $key
	 *
	 * @return string
	 */
	protected function txt($key) {
		return $this->pl->txt('request_' . $key);
	}


	/**
	 * @return bool false when unsuccessful or int request_id when successful
	 */
	public function saveObject($ref_id) {
		if (!$this->fillObject($ref_id)) {
			return false;
		}
		if ($this->object->getId() > 0) {
			$this->object->update();
		} else {
			$this->object->create();
			xdglNotification::sendNew($this->object);
		}

		return $this->object->getId();
	}


	protected function initButtons() {
		if ($this->view) {
			$this->setTitle($this->pl->txt('request_view'));
			$this->addCommandButton('edit', $this->pl->txt('request_edit'));
			if ($this->object->getStatus() != xdglRequest::STATUS_RELEASED) {
				$this->addCommandButton(xdglRequestGUI::CDM_CONFIRM_REFUSE, $this->pl->txt('request_refuse'));
				$this->addCommandButton(xdglRequestGUI::CMD_SELECT_FILE, $this->pl->txt('upload_title'));
			} else {
				$this->addCommandButton(xdglRequestGUI::CMD_REPLACE_FILE, $this->pl->txt('request_replace_file'));
				$this->addCommandButton(xdglRequestGUI::CMD_DELETE_FILE, $this->pl->txt('request_delete_file'));
			}
		} else {
			if ($this->is_new) {
				$this->setTitle($this->pl->txt('request_create'));
				$this->addCommandButton(xdglRequestGUI::CMD_SAVE, $this->pl->txt('request_create'));
			} else {
				$this->setTitle($this->pl->txt('request_edit'));
				$this->addCommandButton(xdglRequestGUI::CMD_UPDATE, $this->pl->txt('request_update'));
			}
		}

		$this->addCommandButton(xdglRequestGUI::CMD_CANCEL, $this->pl->txt('request_cancel'));
	}
}

?>
