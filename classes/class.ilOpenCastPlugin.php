<?php
require_once('./Services/Repository/classes/class.ilRepositoryObjectPlugin.php');
require_once('class.ilObjOpenCastAccess.php');

/**
 * OpenCast repository object plugin
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version 1.0.00
 *
 */
class ilOpenCastPlugin extends ilRepositoryObjectPlugin {

	protected function uninstallCustom() {
		//
	}

//
//	public function txt($a_var) {
//		require_once('./Customizing/global/plugins/Libraries/PluginTranslator/class.sragPluginTranslator.php');
//		return sragPluginTranslator::getInstance($this)->active()->write()->txt($a_var);
//	}

	const XOCT = 'xoct';
	const AR_CUST = './Customizing/global/plugins/Libraries/ActiveRecord/class.ActiveRecord.php';
	const AR_SERV = './Services/ActiveRecord/class.ActiveRecord.php';
	/**
	 * @var ilOpenCastPlugin
	 */
	protected static $cache;


	/**
	 * @return ilOpenCastPlugin
	 */
	public static function getInstance() {
		if (!isset(self::$cache)) {
			//require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/sql/dbupdate.php');
			self::$cache = new self();
		}

		return self::$cache;
	}


	/**
	 * @return string
	 */
	function getPluginName() {
		return self::getStaticPluginName();
	}


	/**
	 * @return string
	 */
	public static function getStaticPluginName() {
		return 'OpenCast';
	}


	/**
	 * @return string
	 */
	public static function getStaticPluginPrefix() {
		return self::XOCT;
	}
}

?>
