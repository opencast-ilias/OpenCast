<?php

require_once __DIR__ . "/../vendor/autoload.php";

use srag\DataTableUI\OpenCast\Implementation\Utils\DataTableUITrait;
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
use srag\Plugins\Opencast\Util\UpdateCheck;

/**
 * OpenCast repository object plugin
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version 1.0.00
 *
 */
class ilOpenCastPlugin extends ilRepositoryObjectPlugin
{
    use DataTableUITrait;

    public const PLUGIN_CLASS_NAME = self::class;

    public const PLUGIN_ID = 'xoct';
    public const PLUGIN_NAME = 'OpenCast';
    /**
     * @var ilDBInterface
     */
    protected $db;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        global $DIC;
        $this->db = $DIC->database();
    }

    protected function beforeUpdate(): bool
    {
        // Check Version
        $check = new UpdateCheck($this->db);
        if (!$check->isUpdatePossible()) {
            throw new ilPluginException(
                'You try to update from a incompatible version of the plugin, please read the infos here: https://github.com/opencast-ilias/OpenCast/blob/main/doc/migration.md'
            );
        }
        return true;
    }

    protected function afterUpdate()
    {
        if (PluginConfig::count() == 0) {
            PluginConfig::importFromXML($this->getDirectory() . '/configuration/default_config.xml');
        }
    }

    protected function uninstallCustom(): bool
    {
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
    public static function getInstance()
    {
        if (!isset(self::$cache)) {
            self::$cache = new self();
        }

        return self::$cache;
    }

    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    public function allowCopy(): bool
    {
        return true;
    }
}
