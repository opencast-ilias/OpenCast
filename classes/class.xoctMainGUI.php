<?php

require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/class.xoctGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/class.xoctConfGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/PublicationUsage/class.xoctPublicationUsageGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/SystemAccount/class.xoctSystemAccountGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/Export/class.xoctConfExportGUI.php');

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
	const TAB_PUBLICATION_USAGE = 'publication_usage';
	const TAB_PERMISSION_TEMPLATES = 'permission_templates';
	const TAB_EXPORT = 'export';
	const TAB_MIGRATION = 'migration';


	/**
	 * @return bool
	 */
	public function executeCommand() {
		$nextClass = $this->ctrl->getNextClass();

		$this->tabs->addTab(self::TAB_SETTINGS, $this->pl->txt('tab_' . self::TAB_SETTINGS), $this->ctrl->getLinkTarget(new xoctConfGUI()));
		$this->tabs->addTab(self::TAB_PUBLICATION_USAGE, $this->pl->txt('tab_'
			. self::TAB_PUBLICATION_USAGE), $this->ctrl->getLinkTarget(new xoctPublicationUsageGUI()));
		$this->tabs->addTab(self::TAB_PERMISSION_TEMPLATES, $this->pl->txt('tab_' . self::TAB_PERMISSION_TEMPLATES), $this->ctrl->getLinkTarget(new xoctPermissionTemplateGUI()));
		$this->tabs->addTab(self::TAB_EXPORT, $this->pl->txt('tab_' . self::TAB_EXPORT), $this->ctrl->getLinkTarget(new xoctConfExportGUI()));
		if (is_file('./Customizing/global/plugins/Services/Repository/RepositoryObject/Scast/classes/class.ilScastPlugin.php')) {
			require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Migration/class.xoctScaMigrationGUI.php');
			$this->tabs->addTab(self::TAB_MIGRATION, $this->pl->txt('tab_' . self::TAB_MIGRATION), $this->ctrl->getLinkTarget(new xoctScaMigrationGUI()));
		}

		switch ($nextClass) {
			case 'xoctpublicationusagegui':
				$this->tabs->setTabActive(self::TAB_PUBLICATION_USAGE);
				$xoctPublicationUsageGUI = new xoctPublicationUsageGUI();
				$this->ctrl->forwardCommand($xoctPublicationUsageGUI);
				break;
			case 'xoctconfexportgui':
				$this->tabs->setTabActive(self::TAB_EXPORT);
				$xoctConfExportGUI = new xoctConfExportGUI();
				$this->ctrl->forwardCommand($xoctConfExportGUI);
				break;
			case 'xoctscamigrationgui':
				$this->tabs->setTabActive(self::TAB_MIGRATION);
				$xoctScaMigrationGUI = new xoctScaMigrationGUI();
				$this->ctrl->forwardCommand($xoctScaMigrationGUI);
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
