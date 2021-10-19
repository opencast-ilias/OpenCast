<?php

use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Workflow\WorkflowAR;

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
	 *
	 */
	protected function afterUpdate()
	{
		if (xoctConf::count() == 0) {
			xoctConf::importFromXML($this->getDirectory() . '/configuration/default_config.xml');
		}
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
		$this->db->dropTable(PublicationUsage::TABLE_NAME, false);
		$this->db->dropTable(xoctConf::TABLE_NAME, false);
		$this->db->dropTable(xoctReport::DB_TABLE, false);
		$this->db->dropTable(WorkflowAR::TABLE_NAME, false);

		return true;
	}


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

	public function allowCopy()
	{
		return true;
	}
}

?>
