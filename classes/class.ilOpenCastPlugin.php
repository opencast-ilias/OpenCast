<?php

use srag\DataTableUI\OpenCast\Implementation\Utils\DataTableUITrait;
use srag\DIC\OpenCast\DICTrait;
use srag\Plugins\Opencast\Model\Cache\Service\DB\DBCacheAR;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Event\EventAdditionsAR;
use srag\Plugins\Opencast\Model\Metadata\Config\Event\MDFieldConfigEventAR;
use srag\Plugins\Opencast\Model\Metadata\Config\Series\MDFieldConfigSeriesAR;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate;
use srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGrant;
use srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGroup;
use srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGroupParticipant;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Report\Report;
use srag\Plugins\Opencast\Model\TermsOfUse\AcceptedToU;
use srag\Plugins\Opencast\Model\UserSettings\UserSetting;
use srag\Plugins\Opencast\Model\Workflow\WorkflowAR;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameter;

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
		if (PluginConfig::count() == 0) {
			PluginConfig::importFromXML($this->getDirectory() . '/configuration/default_config.xml');
		}
	}

	/**
	 * @return bool
	 */
	protected function uninstallCustom() {
		$this->db->dropTable(PermissionGrant::TABLE_NAME, false);
		$this->db->dropTable(PermissionGroupParticipant::TABLE_NAME, false);
		$this->db->dropTable(PermissionGroup::TABLE_NAME, false);
		$this->db->dropTable(ObjectSettings::TABLE_NAME, false);
		$this->db->dropTable(EventAdditionsAR::TABLE_NAME, false);
		$this->db->dropTable(PermissionTemplate::TABLE_NAME, false);
		$this->db->dropTable(PublicationUsage::TABLE_NAME, false);
		$this->db->dropTable(PluginConfig::TABLE_NAME, false);
		$this->db->dropTable(Report::DB_TABLE, false);
		$this->db->dropTable(WorkflowAR::TABLE_NAME, false);
		$this->db->dropTable(WorkflowParameter::TABLE_NAME, false);
		$this->db->dropTable(SeriesWorkflowParameter::TABLE_NAME, false);
		$this->db->dropTable(MDFieldConfigEventAR::TABLE_NAME, false);
		$this->db->dropTable(MDFieldConfigSeriesAR::TABLE_NAME, false);
		$this->db->dropTable(UserSetting::TABLE_NAME, false);
		$this->db->dropTable(AcceptedToU::TABLE_NAME, false);
		$this->db->dropTable(DBCacheAR::TABLE_NAME, false);

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