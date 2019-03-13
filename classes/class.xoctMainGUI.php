<?php
require_once __DIR__ . '/../vendor/autoload.php';
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
	const TAB_VIDEO_PORTAL = 'video_portal';
	const TAB_EXPORT = 'export';
	const TAB_REPORTS = 'reports';

	const SUBTAB_API = 'api';
	const SUBTAB_SERIES = 'series';
	const SUBTAB_EVENTS = 'events';
	const SUBTAB_GROUPS_ROLES = 'groups_roles';
	const SUBTAB_SECURITY = 'security';
	const SUBTAB_ADVANCED = 'advanced';


	/**
	 * @return bool
	 */
	public function executeCommand() {
		$nextClass = $this->ctrl->getNextClass();

		$this->tabs->addTab(self::TAB_SETTINGS, $this->pl->txt('tab_' . self::TAB_SETTINGS), $this->ctrl->getLinkTarget(new xoctConfGUI()));
		$this->tabs->addTab(self::TAB_PUBLICATION_USAGE, $this->pl->txt('tab_'
			. self::TAB_PUBLICATION_USAGE), $this->ctrl->getLinkTarget(new xoctPublicationUsageGUI()));
		$this->tabs->addTab(self::TAB_VIDEO_PORTAL, $this->pl->txt('tab_' . self::TAB_VIDEO_PORTAL), $this->ctrl->getLinkTarget(new xoctPermissionTemplateGUI()));
		$this->tabs->addTab(self::TAB_EXPORT, $this->pl->txt('tab_' . self::TAB_EXPORT), $this->ctrl->getLinkTarget(new xoctConfExportGUI()));
		$this->tabs->addTab(self::TAB_REPORTS, $this->pl->txt('tab_' . self::TAB_REPORTS), $this->ctrl->getLinkTarget(new xoctReportOverviewGUI()));


		switch ($nextClass) {
			case strtolower(xoctPublicationUsageGUI::class):
				$this->tabs->activateTab(self::TAB_PUBLICATION_USAGE);
				$xoctPublicationUsageGUI = new xoctPublicationUsageGUI();
				$this->ctrl->forwardCommand($xoctPublicationUsageGUI);
				break;
			case strtolower(xoctPermissionTemplateGUI::class):
				$this->tabs->activateTab(self::TAB_VIDEO_PORTAL);
				$xoctPermissionTemplateGUI = new xoctPermissionTemplateGUI();
				$this->ctrl->forwardCommand($xoctPermissionTemplateGUI);
				break;
			case strtolower(xoctConfExportGUI::class):
				$this->tabs->activateTab(self::TAB_EXPORT);
				$xoctConfExportGUI = new xoctConfExportGUI();
				$this->ctrl->forwardCommand($xoctConfExportGUI);
				break;
			case strtolower(xoctReportOverviewGUI::class):
				$this->tabs->activateTab(self::TAB_REPORTS);
				$xoctReportOverviewGUI = new xoctReportOverviewGUI();
				$this->ctrl->forwardCommand($xoctReportOverviewGUI);
				break;
			default:
				$this->tabs->activateTab(self::TAB_SETTINGS);
				$this->setSubTabs();
				$xoctConfGUI = new xoctConfGUI();
				$this->ctrl->forwardCommand($xoctConfGUI);
				break;
		}
	}

	protected function setSubTabs() {
		$this->ctrl->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_API);
		$this->tabs->addSubTab(self::SUBTAB_API, $this->pl->txt('subtab_' . self::SUBTAB_API), $this->ctrl->getLinkTarget(new xoctConfGUI()));
		$this->ctrl->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_EVENTS);
		$this->tabs->addSubTab(self::SUBTAB_EVENTS, $this->pl->txt('subtab_' . self::SUBTAB_EVENTS), $this->ctrl->getLinkTarget(new xoctConfGUI()));
		$this->ctrl->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_SERIES);
		$this->tabs->addSubTab(self::SUBTAB_SERIES, $this->pl->txt('subtab_' . self::SUBTAB_SERIES), $this->ctrl->getLinkTarget(new xoctConfGUI()));
		$this->ctrl->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_GROUPS_ROLES);
		$this->tabs->addSubTab(self::SUBTAB_GROUPS_ROLES, $this->pl->txt('subtab_' . self::SUBTAB_GROUPS_ROLES), $this->ctrl->getLinkTarget(new xoctConfGUI()));
		$this->ctrl->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_SECURITY);
		$this->tabs->addSubTab(self::SUBTAB_SECURITY, $this->pl->txt('subtab_' . self::SUBTAB_SECURITY), $this->ctrl->getLinkTarget(new xoctConfGUI()));
		$this->ctrl->setParameterByClass(xoctConfGUI::class, 'subtab_active', self::SUBTAB_ADVANCED);
		$this->tabs->addSubTab(self::SUBTAB_ADVANCED, $this->pl->txt('subtab_' . self::SUBTAB_ADVANCED), $this->ctrl->getLinkTarget(new xoctConfGUI()));
		$this->ctrl->clearParametersByClass(xoctConfGUI::class);
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
