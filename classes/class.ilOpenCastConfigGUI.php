<?php
require_once __DIR__ . '/../vendor/autoload.php';
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
		/**
		 * @var self::dic()->ctrl() ilCtrl
		 */
		self::dic()->ctrl()->setParameterByClass("ilobjcomponentsettingsgui", "ctype", $_GET["ctype"]);
		self::dic()->ctrl()->setParameterByClass("ilobjcomponentsettingsgui", "cname", $_GET["cname"]);
		self::dic()->ctrl()->setParameterByClass("ilobjcomponentsettingsgui", "slot_id", $_GET["slot_id"]);
		self::dic()->ctrl()->setParameterByClass("ilobjcomponentsettingsgui", "plugin_id", $_GET["plugin_id"]);
		self::dic()->ctrl()->setParameterByClass("ilobjcomponentsettingsgui", "pname", $_GET["pname"]);

		self::dic()->mainTemplate()->setTitle(self::dic()->language()->txt("cmps_plugin") . ": " . $_GET["pname"]);
		self::dic()->mainTemplate()->setDescription("");

		self::dic()->tabs()->clearTargets();

		if ($_GET["plugin_id"]) {
			self::dic()->tabs()->setBackTarget(self::dic()->language()->txt("cmps_plugin"), self::dic()->ctrl()->getLinkTargetByClass("ilobjcomponentsettingsgui", "showPlugin"));
		} else {
			self::dic()->tabs()->setBackTarget(self::dic()->language()->txt("cmps_plugins"), self::dic()->ctrl()->getLinkTargetByClass("ilobjcomponentsettingsgui", "listPlugins"));
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
