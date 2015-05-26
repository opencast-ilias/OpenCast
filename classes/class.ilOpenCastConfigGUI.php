<?php

require_once('./Services/Component/classes/class.ilPluginConfigGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/class.xoctConfGUI.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/class.xoctConf.php');
require_once('class.xoctMainGUI.php');

/**
 * ilOpenCastConfigGUI
 *
 * @author             Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy  ilOpenCastConfigGUI: ilObjComponentSettingsGUIs
 */
class ilOpenCastConfigGUI extends ilPluginConfigGUI {

	public function executeCommand() {
		global $ilCtrl, $ilTabs, $lng, $tpl;
		/**
		 * @var $ilCtrl ilCtrl
		 */
		$ilCtrl->setParameterByClass("ilobjcomponentsettingsgui", "ctype", $_GET["ctype"]);
		$ilCtrl->setParameterByClass("ilobjcomponentsettingsgui", "cname", $_GET["cname"]);
		$ilCtrl->setParameterByClass("ilobjcomponentsettingsgui", "slot_id", $_GET["slot_id"]);
		$ilCtrl->setParameterByClass("ilobjcomponentsettingsgui", "plugin_id", $_GET["plugin_id"]);
		$ilCtrl->setParameterByClass("ilobjcomponentsettingsgui", "pname", $_GET["pname"]);

		$tpl->setTitle($lng->txt("cmps_plugin") . ": " . $_GET["pname"]);
		$tpl->setDescription("");

		$ilTabs->clearTargets();

		if ($_GET["plugin_id"]) {
			$ilTabs->setBackTarget($lng->txt("cmps_plugin"), $ilCtrl->getLinkTargetByClass("ilobjcomponentsettingsgui", "showPlugin"));
		} else {
			$ilTabs->setBackTarget($lng->txt("cmps_plugins"), $ilCtrl->getLinkTargetByClass("ilobjcomponentsettingsgui", "listPlugins"));
		}

		$nextClass = $ilCtrl->getNextClass();

		if ($nextClass) {
			$a_gui_object = new xoctMainGUI();
			$ilCtrl->forwardCommand($a_gui_object);
		} else {
			$ilCtrl->redirectByClass(array( 'xoctMainGUI', 'xoctConfGUI' ));
		}
	}


	public function performCommand($cmd) {
	}
}

?>
