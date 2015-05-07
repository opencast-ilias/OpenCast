<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Request/class.xoctRequestGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Config/class.xoctConfigGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Library/class.xoctLibraryGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Config/class.xoctConfig.php');

/**
 * Class xoctMainGUI
 *
 * @author            Fabian Schmid <fs@studer-raimann.ch>
 * @version           1.0.0
 *
 * @ilCtrl_IsCalledBy xoctMainGUI : ilOpenCastConfigGUI
 */
class xoctMainGUI {

	const TAB_SETTINGS = 'settings';
	const TAB_LIBRARIES = 'libraries';
	const TAB_REQUESTS = 'requests';
	/**
	 * @var ilTabsGUI
	 */
	protected $tabs;
	/**
	 * @var ilToolbarGUI
	 */
	protected $toolbar;
	/**
	 * @var ilCtrl
	 */
	protected $ctrl;
	/**
	 * @var ilTemplate
	 */
	protected $tpl;


	public function __construct() {
		global $tpl, $ilCtrl, $ilTabs;
		$this->tpl = $tpl;
		$this->tabs = $ilTabs;
		$this->ctrl = $ilCtrl;
		$this->pl = ilOpenCastPlugin::getInstance();
	}


	/**
	 * @return bool
	 */
	public function executeCommand() {
		$xoctRequestGUI = new xoctRequestGUI();
		$this->tabs->addTab(self::TAB_REQUESTS, $this->pl->txt('tab_' . self::TAB_REQUESTS), $this->ctrl->getLinkTarget($xoctRequestGUI));
		$xoctLibraryGUI = new xoctLibraryGUI();
		if (ilObjOpenCastAccess::isAdmin()) {
			$xoctConfigGUI = new xoctConfigGUI();
			$this->tabs->addTab(self::TAB_SETTINGS, $this->pl->txt('tab_' . self::TAB_SETTINGS), $this->ctrl->getLinkTarget($xoctConfigGUI));
			if (xoctConfig::get(xoctConfig::F_USE_LIBRARIES)) {
				$this->tabs->addTab(self::TAB_LIBRARIES, $this->pl->txt('tab_' . self::TAB_LIBRARIES), $this->ctrl->getLinkTarget($xoctLibraryGUI));
			}
		}
		$nextClass = $this->ctrl->getNextClass();
		if (! xoctConfig::isConfigUpToDate()) {
			ilUtil::sendInfo('Configuraion out of date');
			$nextClass = 'xoctconfiggui';
		}
		global $ilUser;
		if (xoctConfig::get(xoctConfig::F_USE_LIBRARIES) AND
			xoctConfig::get(xoctConfig::F_OWN_LIBRARY_ONLY) AND ! xoctLibrary::isAssignedToAnyLibrary($ilUser)
		) {
			ilUtil::sendInfo('You cannot use OpenCast since you are not assigned to any Library', true);
			ilUtil::redirect('/');
		}

		switch ($nextClass) {
			case 'xoctconfiggui';
				$this->tabs->setTabActive(self::TAB_SETTINGS);
				$this->ctrl->forwardCommand($xoctConfigGUI);

				break;
			case 'xoctlibrarygui';
				$this->tabs->setTabActive(self::TAB_LIBRARIES);
				$this->ctrl->forwardCommand($xoctLibraryGUI);
				break;
			default:
				$this->tabs->setTabActive(self::TAB_REQUESTS);
				$this->ctrl->forwardCommand($xoctRequestGUI);

				break;
		}
		if (xoctConfig::is50()) {
			$this->tpl->getStandardTemplate();
			$this->tpl->show();
		}
	}
}

?>
