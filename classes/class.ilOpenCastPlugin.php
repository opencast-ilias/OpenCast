<?php
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * OpenCast repository object plugin
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version 1.0.00
 *
 */
class ilOpenCastPlugin extends ilRepositoryObjectPlugin {

	const PLUGIN_ID = 'xoct';
	const PLUGIN_NAME = 'OpenCast';
	/**
	 * @var ilDB
	 */
	protected $db;


	/**
	 *
	 */
	public function __construct() {
		parent::__construct();

		global $ilDB;
		$this->db = $ilDB;
	}


	/**
	 * @return bool
	 */
	protected function uninstallCustom() {
		$this->db->dropTable(xoctInvitation::TABLE_NAME, false);
		$this->db->dropTable(xoctIVTGroupParticipant::TABLE_NAME, false);
		$this->db->dropTable(xoctIVTGroup::TABLE_NAME, false);
		$this->db->dropTable(xoctOpenCast::TABLE_NAME, false);
		$this->db->dropTable(xoctEventAdditions::TABLE_NAME, false);
		$this->db->dropTable(xoctPermissionTemplate::TABLE_NAME, false);
		$this->db->dropTable(xoctPublicationUsage::TABLE_NAME, false);
		$this->db->dropTable(xoctSystemAccount::TABLE_NAME, false);
		$this->db->dropTable(xoctConf::TABLE_NAME, false);

		return true;
	}


	//	public function txt($a_var) {
	//		require_once('./Customizing/global/plugins/Libraries/PluginTranslator/class.sragPluginTranslator.php');
	//		return sragPluginTranslator::getInstance($this)->active()->write()->txt($a_var);
	//	}

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
		return self::PLUGIN_NAME;
	}
}

?>
