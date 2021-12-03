<?php

use srag\DataTableUI\OpenCast\Implementation\Utils\DataTableUITrait;
use srag\DIC\OpenCast\DICTrait;
use srag\Plugins\Opencast\Model\Event\EventAdditionsAR;
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

	const PLUGIN_CLASS_NAME = self::class;

	use DataTableUITrait;
	use DICTrait;

	const PLUGIN_ID = 'xoct';
	const PLUGIN_NAME = 'OpenCast';
    /**
     * @var ilDBInterface
     */
	protected $db;


	/**
	 *
	 */
	public function __construct() {
		parent::__construct();

		global $DIC;
		$this->db = $DIC->database();
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
		$this->db->dropTable(EventAdditionsAR::TABLE_NAME, false);
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

	public function updateLanguages($a_lang_keys = null)
	{
		parent::updateLanguages($a_lang_keys);
		self::dataTableUI()->installLanguages(self::plugin());
	}

}