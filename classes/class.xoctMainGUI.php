<?php

require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/class.xoctConfGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/PublicationUsage/class.xoctPublicationUsageGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/SystemAccount/class.xoctSystemAccountGUI.php');

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
	const TAB_SYSTEM_ACCOUNTS = 'system_accounts';
	const TAB_PUBLICATION_USAGE = 'publication_usage';


	/**
	 * @return bool
	 */
	public function executeCommand() {
		$nextClass = $this->ctrl->getNextClass();

		$this->tabs->addTab(self::TAB_SETTINGS, $this->pl->txt('tab_' . self::TAB_SETTINGS), $this->ctrl->getLinkTarget(new xoctConfGUI()));
		$this->tabs->addTab(self::TAB_SYSTEM_ACCOUNTS, $this->pl->txt('tab_'
			. self::TAB_SYSTEM_ACCOUNTS), $this->ctrl->getLinkTarget(new xoctSystemAccountGUI()));
		$this->tabs->addTab(self::TAB_PUBLICATION_USAGE, $this->pl->txt('tab_'
			. self::TAB_PUBLICATION_USAGE), $this->ctrl->getLinkTarget(new xoctPublicationUsageGUI()));

		switch ($nextClass) {
			case 'xoctpublicationusagegui':
				$this->tabs->setTabActive(self::TAB_PUBLICATION_USAGE);
				$xoctPublicationUsageGUI = new xoctPublicationUsageGUI();
				$this->ctrl->forwardCommand($xoctPublicationUsageGUI);
				break;
			case 'xoctsystemaccountgui':
				$this->tabs->setTabActive(self::TAB_SYSTEM_ACCOUNTS);
				$xoctSystemAccountGUI = new xoctSystemAccountGUI();
				$this->ctrl->forwardCommand($xoctSystemAccountGUI);
				break;
			default:
				$this->tabs->setTabActive(self::TAB_SETTINGS);
				$xoctConfGUI = new xoctConfGUI();
				$this->ctrl->forwardCommand($xoctConfGUI);
				break;
		}
	}


	protected function index() {
		// TODO: Implement index() method.
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


	protected function update() {
		// TODO: Implement update() method.
	}


	protected function confirmDelete() {
		// TODO: Implement confirmDelete() method.
	}


	protected function delete() {
		// TODO: Implement delete() method.
	}
}

?>
