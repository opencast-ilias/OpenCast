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
		$users[NULL] = $this->pl->txt('event_owner_select');
		foreach (ilObjOpenCastAccess::getMembers() as $member) {
			$name = ilObjUser::_lookupName($member);
			$users[$member] = $name['lastname'] . ', ' . $name['firstname'];
		}
		foreach (ilObjOpenCastAccess::getAdmins() as $member) {
			$name = ilObjUser::_lookupName($member);
			$users[$member] = $name['lastname'] . ', ' . $name['firstname'];
		}

		$sel->setOptions($users);
		$sel->setRequired(true);
		$this->addItem($sel);
	}


	public function fillForm() {
//		echo '<pre>' . print_r($this->object, 1) . '</pre>';
//		exit;
		$user_id = NULL;
		$owner = $this->object->getOwner();

		if ($owner instanceof xoctUser) {
			$user_id = $owner->getIliasUserId();
		}
		$array = array(
			self::F_OWNER => $user_id,
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

		$xoctUser = xoctUser::getInstance(new ilObjUser($this->getInput(self::F_OWNER)));
		$this->object->setOwner($xoctUser);

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
		$this->object->update();

		return true;
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


