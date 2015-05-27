<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctGUI.php');
require_once('class.xoctConfFormGUI.php');

/**
 * Class xoctConfGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy xoctConfGUI : xoctMainGUI
 */
class xoctConfGUI extends xoctGUI {

	/**
	 * @param $key
	 *
	 * @return string
	 */
	public function txt($key) {
		return $this->pl->txt('config_' . $key);
	}


	public function index() {
		$xoctConfFormGUI = new xoctConfFormGUI($this);
		$xoctConfFormGUI->fillForm();
		$this->tpl->setContent($xoctConfFormGUI->getHTML());
	}


	protected function update() {
		$xoctConfFormGUI = new xoctConfFormGUI($this);
		$xoctConfFormGUI->setValuesByPost();
		if ($xoctConfFormGUI->saveObject()) {
			ilUtil::sendSuccess($this->txt('msg_success'), true);
			$this->ctrl->redirect($this, self::CMD_STANDARD);
		}
		$this->tpl->setContent($xoctConfFormGUI->getHTML());
	}


	protected function confirmDelete() {
		// TODO: Implement confirmDelete() method.
	}


	protected function delete() {
		// TODO: Implement delete() method.
	}


	protected function add() {
		// TODO: Implement add() method.
	}


	protected function create() {
		// TODO: Implement create() method.
	}


	protected function edit() {
		// TODO: Implement edit() method.
	}
}