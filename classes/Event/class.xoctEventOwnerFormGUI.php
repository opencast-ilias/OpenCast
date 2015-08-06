<?php
require_once('./Services/Form/classes/class.ilPropertyFormGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.ilObjOpenCastAccess.php');

/**
 * Class xoctEventFormGUI
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctEventOwnerFormGUI extends ilPropertyFormGUI {

	const F_OWNER = 'owner';
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
	public function __construct($parent_gui, xoctEvent $object, xoctOpenCast $xoctOpenCast) {
		global $ilCtrl, $lng, $tpl;
		$this->object = $object;
		$this->xoctOpenCast = $xoctOpenCast;
		$this->parent_gui = $parent_gui;
		$this->ctrl = $ilCtrl;
		$this->pl = ilOpenCastPlugin::getInstance();
		$this->ctrl->saveParameter($parent_gui, xoctEventGUI::IDENTIFIER);
		$this->lng = $lng;
		$this->is_new = ($this->object->getIdentifier() == '');
		$this->initForm();
	}


	protected function initForm() {
		$this->setTarget('_top');
		$this->setFormAction($this->ctrl->getFormAction($this->parent_gui));
		$this->initButtons();

		$sel = new ilSelectInputGUI($this->txt(self::F_OWNER), self::F_OWNER);
		$users = array();
		foreach (ilObjOpenCastAccess::getMembers() as $member) {
			$name = ilObjUser::_lookupName($member);
			$users[$member] = $name['lastname'] . ', ' . $name['firstname'];
		}

		$sel->setOptions($users);
		$sel->setRequired(true);
		$this->addItem($sel);
	}


	public function fillForm() {
		global $ilUser;
		foreach ($this->object->getAcls() as $acl) {
//			echo '<pre>' . print_r($acl, 1) . '</pre>';
			if ($acl->isIVTAcl()) {
				$role = $acl->getRole();
			}
		}


		$acl = new xoctAcl();
		$acl->setAllow(true);
		$acl->setAction(xoctAcl::READ);
		$acl->setRole(xoctUser::getInstance($ilUser)->getIVTRoleName());
		$this->object->addAcl($acl);
		$this->object->update();


		$user_id = xoctUser::lookupUserIdForIVTRole($role);

//		echo '<pre>' . print_r($role, 1) . '</pre>';

		$array = array(
			self::F_OWNER => $this->object->getTitle(),
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
		//$this->object->setTitle($this->getInput(self::F_OWNER));

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

		//		$this->object->setAcls();

		//		$this->object->update();

		//		return $this->object->getIdentifier();
	}


	protected function initButtons() {
		$this->setTitle($this->txt('edit_owner'));
		$this->addCommandButton(xoctEventGUI::CMD_UPDATE_OWNER, $this->txt(xoctEventGUI::CMD_UPDATE_OWNER));
		$this->addCommandButton(xoctEventGUI::CMD_CANCEL, $this->txt(xoctEventGUI::CMD_CANCEL));
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

		foreach ($this->object->getPublications() as $pub) {
			$h = new ilFormSectionHeaderGUI();
			$h->setTitle($pub->getChannel());
			$this->addItem($h);

			$te = new ilCustomInputGUI('Publication ' . $pub->getChannel(), 'pub_' . $pub->getChannel());
			$te->setHtml('<table><tr><td>' . $pub->__toCsv("</td><td>", "</td></tr><tr><td>") . '</td></tr></table>');
			$this->addItem($te);

			foreach ($pub->getMedia() as $med) {
				$te = new ilCustomInputGUI($med->getId(), $med->getId());
				$te->setHtml('<table><tr><td>' . $med->__toCsv("</td><td>", "</td></tr><tr><td>") . '</td></tr></table>');
				$this->addItem($te);
			}
		}

		$h = new ilFormSectionHeaderGUI();
		$h->setTitle('ACL');
		$this->addItem($h);

		foreach ($this->object->getAcls() as $acl) {
			$te = new ilCustomInputGUI($acl->getRole(), $acl->getRole());
			$te->setHtml('<table><tr><td>' . $acl->__toCsv("</td><td>", "</td></tr><tr><td>") . '</td></tr></table>');
			$this->addItem($te);
		}
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


