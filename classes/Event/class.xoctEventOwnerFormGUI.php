<?php
use srag\DIC\OpenCast\DICTrait;
/**
 * Class xoctEventFormGUI
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctEventOwnerFormGUI extends ilPropertyFormGUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

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
		parent::__construct();
		$this->object = $object;
		$this->xoctOpenCast = $xoctOpenCast;
		$this->parent_gui = $parent_gui;
		self::dic()->ctrl()->saveParameter($parent_gui, xoctEventGUI::IDENTIFIER);
		$this->is_new = ($this->object->getIdentifier() == '');
		$this->initForm();
	}


	protected function initForm() {
		$this->setTarget('_top');
		$this->setFormAction(self::dic()->ctrl()->getFormAction($this->parent_gui));
		$this->initButtons();

		$sel = new ilSelectInputGUI($this->txt(self::F_OWNER), self::F_OWNER);
		$users = array();
		$users[NULL] = self::plugin()->translate('event_owner_select');
		foreach (array_merge(ilObjOpenCastAccess::getMembers(), ilObjOpenCastAccess::getAdmins(), ilObjOpenCastAccess::getTutors()) as $member) {
			$xoctUser = xoctUser::getInstance(new ilObjuser($member));
			if ($xoctUser->getIdentifier()) {
				$users[$member] = $xoctUser->getNamePresentation();
			}
		}

		$sel->setOptions($users);
		//		$sel->setRequired(true);
		$this->addItem($sel);
	}


	public function fillForm() {
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
		if (!$this->checkInput()) {
			return false;
		}

		$owner = $this->getInput(self::F_OWNER);
		if ($owner) {
			$xoctUser = xoctUser::getInstance(new ilObjUser($owner));
			$this->object->setOwner($xoctUser);
		} else {
			$this->object->removeOwner();
		}

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
		return self::plugin()->translate('event_' . $key . '_info');
	}


	/**
	 * @return bool|string
	 */
	public function saveObject() {
		if (!$this->fillObject()) {
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


