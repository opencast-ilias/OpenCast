<?php
use srag\DIC\OpencastObject\DICTrait;

/**
 * Class xoctGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
abstract class xoctGUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpencastObjectPlugin::class;

	const CMD_STANDARD = 'index';
	const CMD_ADD = 'add';
	const CMD_SAVE = 'save';
	const CMD_CREATE = 'create';
	const CMD_EDIT = 'edit';
	const CMD_UPDATE = 'update';
	const CMD_CONFIRM = 'confirmDelete';
	const CMD_DELETE = 'delete';
	const CMD_CANCEL = 'cancel';
	const CMD_VIEW = 'view';


	public function executeCommand() {
		$nextClass = self::dic()->ctrl()->getNextClass();

		switch ($nextClass) {
			default:
				$cmd = self::dic()->ctrl()->getCmd(self::CMD_STANDARD);
				$this->performCommand($cmd);
				break;
		}
	}


	/**
	 * @param $cmd
	 */
	protected function performCommand($cmd) {
		$this->{$cmd}();
	}


	abstract protected function index();


	abstract protected function add();


	abstract protected function create();


	abstract protected function edit();


	abstract protected function update();


	abstract protected function confirmDelete();


	abstract protected function delete();


	protected function cancel() {
		self::dic()->ctrl()->redirect($this, self::CMD_STANDARD);
	}


	/**
	 * @param $a
	 * @param $b
	 *
	 * @return int
	 */
	protected function compareStdClassByName($a, $b) {
		return strcasecmp($a->name, $b->name);
	}
}

?>
