<?php

declare(strict_types=1);
require_once __DIR__ . "/../vendor/autoload.php";

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
use srag\Plugins\Opencast\Container\Init;
use srag\Plugins\Opencast\Container\Container;

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
    public const PLUGIN_CLASS_NAME = self::class;

    public const PLUGIN_ID = 'xoct';
    public const PLUGIN_NAME = 'OpenCast';
    // Toggle duplication capability, turning to off as it creates confusion!
    public const ALLOW_DUPLICATION = false;
    /**
     * @var ilDBInterface|null
     */
    protected $_db = null; // to have compatibility with ILAIS 7 and 8, we double introduce the property

    private $is_new_installation = false;

    protected function init(): void
    {
        // we create the Opencast Container here and
        global $DIC;
        /** @var Container $opencastContainer */
        global $opencastContainer;
        $this->_db = $DIC->database();
        $opencastContainer = Init::init($DIC);
    }

    protected function beforeUpdate(): bool
    {
        // Check Version
        $check = new UpdateCheck($this->_db);
        $this->is_new_installation = $check->isNewInstallation();
        if (!$check->isUpdatePossible()) {
            throw new ilPluginException(
                'You try to update from a incompatible version of the plugin, please read the infos here: https://github.com/opencast-ilias/OpenCast/blob/main/doc/migration.md'
            );
        }
        return true;
    }

    protected function afterUpdate(): void
    {
        if ($this->is_new_installation) {
            PluginConfig::importFromXML($this->getDirectory() . '/configuration/default_config.xml');
        }
    }

    protected function uninstallCustom(): void
    {
        $this->_db->dropTable(PermissionGrant::TABLE_NAME, false);
        $this->_db->dropTable(PermissionGroupParticipant::TABLE_NAME, false);
        $this->_db->dropTable(PermissionGroup::TABLE_NAME, false);
        $this->_db->dropTable(ObjectSettings::TABLE_NAME, false);
        $this->_db->dropTable(EventAdditionsAR::TABLE_NAME, false);
        $this->_db->dropTable(PermissionTemplate::TABLE_NAME, false);
        $this->_db->dropTable(PublicationUsage::TABLE_NAME, false);
        $this->_db->dropTable(PluginConfig::TABLE_NAME, false);
        $this->_db->dropTable(Report::DB_TABLE, false);
        $this->_db->dropTable(WorkflowAR::TABLE_NAME, false);
        $this->_db->dropTable(WorkflowParameter::TABLE_NAME, false);
        $this->_db->dropTable(SeriesWorkflowParameter::TABLE_NAME, false);
        $this->_db->dropTable(MDFieldConfigEventAR::TABLE_NAME, false);
        $this->_db->dropTable(MDFieldConfigSeriesAR::TABLE_NAME, false);
        $this->_db->dropTable(UserSetting::TABLE_NAME, false);
        $this->_db->dropTable(AcceptedToU::TABLE_NAME, false);
        $this->_db->dropTable('xoct_cache', false);
    }

    /**
     * @var ilOpenCastPlugin|null
     */
    protected static $cache = null;

    public static function getInstance(): ilOpenCastPlugin
    {
        global $DIC;
        if (isset(self::$cache)) {
            return self::$cache;
        }

        // check if we are in ILIAS 8 context
        if (isset($DIC['component.factory'])) {
            /** @var ilComponentFactory $component_factory */
            $component_factory = $DIC['component.factory'];
            /** @var $plugin ilOpenCastPlugin */
            return self::$cache = $component_factory->getPlugin('xoct');
        }
        // otherwise we are in ILIAS 7 context
        return self::$cache = new self();
    }


    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    public function allowCopy(): bool
    {
        // No more copy!
        return self::ALLOW_DUPLICATION;
    }

    public function install(): void
    {
        if (PHP_SAPI === 'cli') {
            $this->update();
        }
        parent::afterInstall();
    }
}
