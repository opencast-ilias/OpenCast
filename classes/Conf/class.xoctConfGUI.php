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
		return self::plugin()->translate('config_' . $key);
	}


	/**
	 *
	 */
	public function index() {
		self::dic()->ctrl()->saveParameter($this, 'subtab_active');
		$subtab_active = $_GET['subtab_active'] ? $_GET['subtab_active'] : xoctMainGUI::SUBTAB_API;
		self::dic()->tabs()->setSubTabActive($subtab_active);
		$xoctConfFormGUI = new xoctConfFormGUI($this, $subtab_active);
		$xoctConfFormGUI->fillForm();
		self::dic()->mainTemplate()->setContent($xoctConfFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function update() {
		self::dic()->ctrl()->saveParameter($this, 'subtab_active');
		$subtab_active = $_GET['subtab_active'] ? $_GET['subtab_active'] : xoctMainGUI::SUBTAB_API;
		$xoctConfFormGUI = new xoctConfFormGUI($this, $subtab_active);
		$xoctConfFormGUI->setValuesByPost();
		if ($xoctConfFormGUI->saveObject()) {
			ilUtil::sendSuccess($this->txt('msg_success'), true);
			self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
		}
		self::dic()->mainTemplate()->setContent($xoctConfFormGUI->getHTML());
	}


	/**
	 *
	 */
	protected function confirmDelete() {
	}


	/**
	 *
	 */
	protected function delete() {
	}


	/**
	 *
	 */
	protected function add() {
	}


	/**
	 *
	 */
	protected function create() {
	}


	/**
	 *
	 */
	protected function edit() {
	}
}