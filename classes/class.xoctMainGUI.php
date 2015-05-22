<?php

require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctGUI.php');

/**
 * Class xoctMainGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           1.0.0
 *
 * @ilCtrl_IsCalledBy xoctMainGUI : ilOpenCastConfigGUI
 */
class xoctMainGUI extends xoctGUI {

	const TAB_SETTINGS = 'settings';


	/**
	 * @return bool
	 */
	public function executeCommand() {
		$nextClass = $this->ctrl->getNextClass();

		$this->tabs->addTab(self::TAB_SETTINGS, $this->pl->txt('tab_' . self::TAB_SETTINGS), $this->ctrl->getLinkTargetByClass('xoctConfGUI'));

		switch ($nextClass) {
			default:
				$this->tabs->setTabActive(self::TAB_SETTINGS);
				$xoctConfGUI = new xoctConfGUI();
				$this->ctrl->forwardCommand($xoctConfGUI);
				break;
		}
	}
}

?>
