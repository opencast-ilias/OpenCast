<?php
require_once('class.xoctDynLan.php');
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
class ilOpenCastPlugin extends ilRepositoryObjectPlugin implements xoctDynLanInterface {

	protected function uninstallCustom() {
		//
	}


	/**
	 * @return string
	 */
	public function getCsvPath() {
		return './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/lang/lang.csv';
	}


	/**
	 * @return string
	 */
	public function getAjaxLink() {
		return NULL;
	}


	/**
	 * @param $a_var
	 *
	 * @return string
	 */
	//public function txt($a_var) {
	//	return xoctDynLan::getInstance($this, xoctDynLan::MODE_DEV)->txt($a_var);
	//}


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
		if (! isset(self::$cache)) {
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
