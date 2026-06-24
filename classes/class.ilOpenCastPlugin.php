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

    private bool $is_new_installation = false;

    protected function init(): void
    {
        // we create the Opencast Container here and
        global $DIC;
        $this->_db = $DIC->database();
    }

    protected function beforeUpdate(): bool
    {
        if (PHP_SAPI !== 'cli') {
            global $DIC;
            $DIC->ui()->mainTemplate()->setOnScreenMessage(
                'failure',
                'Please run the update with the command line interface (CLI) only! The Plugin uses DB Update steps which are not available in the ILIAS GUI. `php cli/setup.php install --legacy-plugin OpenCast`',
                true
            );
            return false;
        }

        $check = new UpdateCheck($this->_db);
        $this->is_new_installation = $check->isNewInstallation();
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

    public static function _getIcon(string $a_type): string
    {
        return './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/images/icon_xoct.svg';
    }

//    public function getDirectory(): string
//    {
//        return realpath(parent::getDirectory()); // TODO: Change the autogenerated stub
//    }

    /**
     * @description This is the easiest way to fix all locations which use a template.
     */
    public function getTemplate(string $a_template, bool $a_par1 = true, bool $a_par2 = true): ilTemplate
    {
        return new ilTemplate(
            $this->getTemplatePath($a_template),
            $a_par1,
            $a_par2,
            'public/Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast'
        );
    }

    public function getTemplatePath(string $a_template, bool $relative_from_customizing = false): string
    {
        // remove a leading "default/" if it exists
        if (str_starts_with($a_template, 'default/')) {
            $a_template = substr($a_template, 8);
        }

        if ($relative_from_customizing) {
            $str = __DIR__ . '/../templates/default/' . $a_template;
            // cut everything before "Customizing/"
            $pos = strpos($str, '/Customizing/');
            if ($pos !== false) {
                return '.' . substr($str, $pos);
            }

            return $str;
        }

        return $a_template;
    }

    public function getRelativeDirectory(): string
    {
        $absolute_path = realpath(__DIR__ . '/../');
        // cut everything before /Customizing/
        $pos = strpos($absolute_path, '/Customizing/');
        if ($pos !== false) {
            return '.' . substr($absolute_path, $pos);
        }

        // give it another try as it is a well known path for repositories.
        return './Customizing/global/plugins/Services/Repository/RepositoryObject/' . $this->getPluginName();
    }

    public function getStyleSheetLocation(string $a_css_file): string
    {
        return $this->getRelativeDirectory() . '/templates/' . $a_css_file;
    }

}
