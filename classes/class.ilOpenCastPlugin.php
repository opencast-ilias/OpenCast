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
class ilOpenCastPlugin extends ilRepositoryObjectPlugin
{
    
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
     * @var ilOpenCastPlugin
     */
    protected static $cache;
    
    public static function getInstance() : ilOpenCastPlugin
    {
        if (!isset(self::$cache)) {
            self::$cache = new self();
        }
        
        return self::$cache;
    }
    
    /**
     *
     */
    public function __construct()
    {
        global $DIC;
        $this->db = $DIC->database();
        
        try {
            parent::__construct();
        } catch (ilPluginException $e) {
            // Display Error-Message
            $this->showRenamingNoticeAndExit();
        }
    }
    
    protected function afterUpdate()
    {
        if (PluginConfig::count() == 0) {
            PluginConfig::importFromXML($this->getDirectory() . '/configuration/default_config.xml');
        }
    }
    
    protected function beforeUpdate()
    {
        try {
            $this->checkPluginDirectory();
        } catch (ilPluginException $e) {
            $this->showRenamingNoticeAndExit();
        }
        return true;
    }
    
    /**
     * @return bool
     */
    protected function uninstallCustom()
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
    
    public function getPluginName()
    {
        return self::PLUGIN_NAME;
    }
    
    protected function showRenamingNoticeAndExit(string $additional_info = null) : void
    {
        echo $additional_info !== null ? $additional_info . nl2br("\n\n") : '';
        echo $this->parseRenamingInfo();
        exit();
    }
    
    protected function parseRenamingInfo() : string
    {
        return nl2br(file_get_contents(__DIR__ . "/../doc/RENAMING_PLUGIN.md"));
    }
    
    /**
     * @throws Exception
     */
    protected function checkPluginDirectory() : void
    {
        // The Plugins has been renamed in 2022. Therefore, we have to check if
        // the current Plugin-Name is equivalent to the directory.
        $matches = [];
        $plugin_name = (string) $this->getPluginName();
        preg_match('/.*RepositoryObject\/(?<directory_name>.*)\/classes.*/m', __DIR__, $matches);
        if (!isset($matches['directory_name']) || strlen($matches['directory_name'] < 8)) {
            throw new ilPluginException('Could not determine directory name of plugin');
        }
        
        $directory_name = $matches['directory_name'];
        if ($plugin_name !== $directory_name) {
            throw new ilPluginException(
                "The Plugin directory `$directory_name` does not match the plugin name `$plugin_name`"
            );
        }
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
