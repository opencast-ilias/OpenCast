<?php
use srag\DIC\OpenCast\DICTrait;
/**
 * ilOpenCastConfigGUI
 *
 * @author             Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy  ilOpenCastConfigGUI: ilObjComponentSettingsGUIs
 */
class ilOpenCastConfigGUI extends ilPluginConfigGUI {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;
	
	public function executeCommand() {
		global $DIC;
		$ilTabs = $DIC['ilTabs'];
		$lng = $DIC['lng'];
		/**
		 * @var self::dic()->ctrl() ilCtrl
		 */
		self::dic()->ctrl()->setParameterByClass("ilobjcomponentsettingsgui", "ctype", $_GET["ctype"]);
		self::dic()->ctrl()->setParameterByClass("ilobjcomponentsettingsgui", "cname", $_GET["cname"]);
		self::dic()->ctrl()->setParameterByClass("ilobjcomponentsettingsgui", "slot_id", $_GET["slot_id"]);
		self::dic()->ctrl()->setParameterByClass("ilobjcomponentsettingsgui", "plugin_id", $_GET["plugin_id"]);
		self::dic()->ctrl()->setParameterByClass("ilobjcomponentsettingsgui", "pname", $_GET["pname"]);

		self::dic()->mainTemplate()->setTitle($lng->txt("cmps_plugin") . ": " . $_GET["pname"]);
		self::dic()->mainTemplate()->setDescription("");

		$ilTabs->clearTargets();

		if ($_GET["plugin_id"]) {
			$ilTabs->setBackTarget($lng->txt("cmps_plugin"), self::dic()->ctrl()->getLinkTargetByClass("ilobjcomponentsettingsgui", "showPlugin"));
		} else {
			$ilTabs->setBackTarget($lng->txt("cmps_plugins"), self::dic()->ctrl()->getLinkTargetByClass("ilobjcomponentsettingsgui", "listPlugins"));
		}

		$nextClass = self::dic()->ctrl()->getNextClass();

		if ($nextClass) {
			$a_gui_object = new xoctMainGUI();
			self::dic()->ctrl()->forwardCommand($a_gui_object);
		} else {
			self::dic()->ctrl()->redirectByClass(array( 'xoctMainGUI', 'xoctConfGUI' ));
		}
	}


	public function performCommand($cmd) {
	}
}

?>
