<?php
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
		$this->ctrl->saveParameter($this, 'subtab_active');
		$subtab_active = $_GET['subtab_active'] ? $_GET['subtab_active'] : xoctMainGUI::SUBTAB_API;
		$this->tabs->setSubTabActive($subtab_active);
		$xoctConfFormGUI = new xoctConfFormGUI($this, $subtab_active);
		$xoctConfFormGUI->fillForm();
		$this->tpl->setContent($xoctConfFormGUI->getHTML());
	}


	protected function update() {
		$this->ctrl->saveParameter($this, 'subtab_active');
		$subtab_active = $_GET['subtab_active'] ? $_GET['subtab_active'] : xoctMainGUI::SUBTAB_API;
		$xoctConfFormGUI = new xoctConfFormGUI($this, $subtab_active);
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