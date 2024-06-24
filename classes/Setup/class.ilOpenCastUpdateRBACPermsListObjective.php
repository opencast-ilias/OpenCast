<?php
declare(strict_types=1);

use ILIAS\Setup\Environment;
use ILIAS\Setup\NullConfig;
use ILIAS\Setup;
use ILIAS\DI;

/**
 * Class ilOpenCastUpdateRBACPermsListObjective
 *
 * @author Farbod Zamani Boroujeni <zamani@elan-ev.de>
*/
class ilOpenCastUpdateRBACPermsListObjective extends ilSetupObjective /* Setup\Objective */
{
    public function __construct()
    {
        parent::__construct(new NullConfig());
    }

    /**
     * @inheritDoc
     */
    public function getHash(): string
    {
        return hash('sha256', self::class);
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return 'Updating Opencast RBAC permissions list of each Object to add (Record, Download and Schedule) perms' .
                ' based on their current permissions set. By admin decision, the copy right can also be removed!';
    }

    /**
     * @inheritDoc
     */
    public function isNotable(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getPreconditions(Environment $environment): array
    {
        return [
            new \ilDatabaseInitializedObjective(),
            new \ilIniFilesLoadedObjective(),
            new \ilSettingsFactoryExistsObjective(),
            new \ilComponentRepositoryExistsObjective(),
            new \ilComponentFactoryExistsObjective(),
        ];
    }

    /**
     * @inheritDoc
     *
     * Additional conceptional info:
     * - Download:      (Allowed in the Object settings) = Download (On) / (NOT Allowed) = Download (Off)
     * - Video Edit:    (On) = Upload, Record, Schedule, Video Edit / (Off) = [Video Edit (Off)]
     * - Upload:        (On) = Upload, Record, [Schedule (off)] / (Off) = [Upload (Off)]
     * - Edit Settings: (On) = Edit Settings, [Edit Videos (off)], [Schedule (off)] -
     *                      / (Off) = [Edit Settings (Off)], [Edit Videos (off)], [Schedule (off)]
     */
    public function achieve(Environment $environment): Environment
    {
        $component_repository = $environment->getResource(Setup\Environment::RESOURCE_COMPONENT_REPOSITORY);
        $component_factory = $environment->getResource(Setup\Environment::RESOURCE_COMPONENT_FACTORY);
        $info = $component_repository->getPluginByName(ilOpenCastPlugin::PLUGIN_NAME);

        if (!$info->supportsCLISetup()) {
            throw new \RuntimeException(
                "OpenCast plugin does not support command line setup."
            );
        }

        $admin_interaction = $environment->getResource(Setup\Environment::RESOURCE_ADMIN_INTERACTION);

        $message =
            "You are about to perform an update action on RBAC record sets of the current OpenCast objects.\n" .
            "This is considered as a risky action, because in case of any error,\n" .
            "the action and the data is irreversible. Are you sure you would like to perform this action?.\n";

        if (!$admin_interaction->confirmOrDeny($message)) {
            throw new Setup\NoConfirmationException($message);
        }

        $ORIG_DIC = $this->initEnvironment($environment, $component_repository, $component_factory);
        $db = $GLOBALS['DIC']['ilDB'];

        $edit_settings_op_id = ilRbacReview::_getCustomRBACOperationId("write");
        // $copy_op_id = ilRbacReview::_getCustomRBACOperationId("copy");

        $upload_op_id = ilRbacReview::_getCustomRBACOperationId("rep_robj_xoct_perm_upload");
        $edit_videos_op_id = ilRbacReview::_getCustomRBACOperationId("rep_robj_xoct_perm_edit_videos");

        $download_op_id = ilRbacReview::_getCustomRBACOperationId("rep_robj_xoct_perm_download");
        $record_op_id = ilRbacReview::_getCustomRBACOperationId("rep_robj_xoct_perm_record");
        $schedule_op_id = ilRbacReview::_getCustomRBACOperationId("rep_robj_xoct_perm_schedule");

        // Get all current opencast object records.
        $set = $db->query(
            'SELECT xoct_data.obj_id, xoct_data.streaming_only, object_reference.ref_id FROM xoct_data
            INNER JOIN object_reference ON object_reference.obj_id = xoct_data.obj_id',
        );
        while ($row = $db->fetchAssoc($set)) {
            $obj_id = (int) $row["obj_id"];
            $ref_id = (int) $row["ref_id"];
            $no_download = (bool) $row["streaming_only"];

            $parent_obj = ilObjOpenCast::_getParentCourseOrGroup($ref_id);
            if (empty($parent_obj)) {
                continue;
            }

            // Admins perms.
            if (method_exists($parent_obj, 'getDefaultAdminRole')) {
                $admin_role_id = $parent_obj->getDefaultAdminRole();
                $admin_ops_ids = $GLOBALS["DIC"]["rbacreview"]->getActiveOperationsOfRole($ref_id, $admin_role_id);

                // Take care of download.
                if (!$no_download && !in_array($download_op_id, $admin_ops_ids)) { // download allowed, add the download op id
                    $admin_ops_ids[] = $download_op_id;
                } else if ($no_download && in_array($download_op_id, $admin_ops_ids)) { // download NOT allowed, remove download op
                    unset($admin_ops_ids[array_search($download_op_id,  $admin_ops_ids)]);
                }

                if (in_array($edit_videos_op_id, $admin_ops_ids)) {
                    $admin_ops_ids[] = $upload_op_id;
                    $admin_ops_ids[] = $record_op_id;
                    $admin_ops_ids[] = $schedule_op_id;
                } else if (in_array($upload_op_id, $admin_ops_ids)) {
                    $admin_ops_ids[] = $record_op_id;
                    // Taking out Schedule if by any change is there, that is unlikely if this Objective is freshly achieved!
                    if (in_array($schedule_op_id, $admin_ops_ids)) {
                        unset($admin_ops_ids[array_search($schedule_op_id,  $admin_ops_ids)]);
                    }
                } else if (in_array($edit_settings_op_id, $admin_ops_ids)) {
                    if (in_array($edit_videos_op_id, $admin_ops_ids)) {
                        unset($admin_ops_ids[array_search($edit_videos_op_id,  $admin_ops_ids)]);
                    }
                    // Taking out Schedule if by any change is there, that is unlikely if this Objective is freshly achieved!
                    if (in_array($schedule_op_id, $admin_ops_ids)) {
                        unset($admin_ops_ids[array_search($schedule_op_id,  $admin_ops_ids)]);
                    }
                }

                $admin_ops_ids = array_map('intval', array_unique($admin_ops_ids));
                $GLOBALS["DIC"]["rbacadmin"]->grantPermission($admin_role_id, $admin_ops_ids, $ref_id);
            }

            // Tutor perms.
            if (method_exists($parent_obj, 'getDefaultTutorRole')) {
                $tutor_role_id = $parent_obj->getDefaultTutorRole();
                $tutor_ops_ids = $GLOBALS["DIC"]["rbacreview"]->getActiveOperationsOfRole($ref_id, $tutor_role_id);

                // Take care of download.
                if (!$no_download && !in_array($download_op_id, $tutor_ops_ids)) { // download allowed, add the download op id
                    $tutor_ops_ids[] = $download_op_id;
                } else if ($no_download && in_array($download_op_id, $tutor_ops_ids)) { // download NOT allowed, remove download op
                    unset($tutor_ops_ids[array_search($download_op_id,  $tutor_ops_ids)]);
                }

                if (in_array($edit_videos_op_id, $tutor_ops_ids)) {
                    $tutor_ops_ids[] = $upload_op_id;
                    $tutor_ops_ids[] = $record_op_id;
                    $tutor_ops_ids[] = $schedule_op_id;
                } else if (in_array($upload_op_id, $tutor_ops_ids)) {
                    $tutor_ops_ids[] = $record_op_id;
                    // Taking out Schedule if by any change is there, that is unlikely if this Objective is freshly achieved!
                    if (in_array($schedule_op_id, $tutor_ops_ids)) {
                        unset($tutor_ops_ids[array_search($schedule_op_id,  $tutor_ops_ids)]);
                    }
                } else if (in_array($edit_settings_op_id, $tutor_ops_ids)) {
                    if (in_array($edit_videos_op_id, $tutor_ops_ids)) {
                        unset($tutor_ops_ids[array_search($edit_videos_op_id,  $tutor_ops_ids)]);
                    }
                    // Taking out Schedule if by any change is there, that is unlikely if this Objective is freshly achieved!
                    if (in_array($schedule_op_id, $tutor_ops_ids)) {
                        unset($tutor_ops_ids[array_search($schedule_op_id,  $tutor_ops_ids)]);
                    }
                }

                $tutor_ops_ids = array_map('intval', array_unique($tutor_ops_ids));
                $GLOBALS["DIC"]["rbacadmin"]->grantPermission($tutor_role_id, $tutor_ops_ids, $ref_id);
            }

            // Member perms.
            if (method_exists($parent_obj, 'getDefaultMemberRole')) {
                $member_role_id = $parent_obj->getDefaultMemberRole();
                $member_ops_ids = $GLOBALS["DIC"]["rbacreview"]->getActiveOperationsOfRole($ref_id, $member_role_id);

                // Take care of download.
                if (!$no_download && !in_array($download_op_id, $member_ops_ids)) { // download allowed, add the download op id
                    $member_ops_ids[] = $download_op_id;
                } else if ($no_download && in_array($download_op_id, $member_ops_ids)) { // download NOT allowed, remove download op
                    unset($member_ops_ids[array_search($download_op_id,  $member_ops_ids)]);
                }

                if (in_array($edit_videos_op_id, $member_ops_ids)) {
                    $member_ops_ids[] = $upload_op_id;
                    $member_ops_ids[] = $record_op_id;
                    $member_ops_ids[] = $schedule_op_id;
                } else if (in_array($upload_op_id, $member_ops_ids)) {
                    $member_ops_ids[] = $record_op_id;
                    // Taking out Schedule if by any change is there, that is unlikely if this Objective is freshly achieved!
                    if (in_array($schedule_op_id, $member_ops_ids)) {
                        unset($member_ops_ids[array_search($schedule_op_id,  $member_ops_ids)]);
                    }
                } else if (in_array($edit_settings_op_id, $member_ops_ids)) {
                    if (in_array($edit_videos_op_id, $member_ops_ids)) {
                        unset($member_ops_ids[array_search($edit_videos_op_id,  $member_ops_ids)]);
                    }
                    // Taking out Schedule if by any change is there, that is unlikely if this Objective is freshly achieved!
                    if (in_array($schedule_op_id, $member_ops_ids)) {
                        unset($member_ops_ids[array_search($schedule_op_id,  $member_ops_ids)]);
                    }
                }

                $member_ops_ids = array_map('intval', array_unique($member_ops_ids));
                $GLOBALS["DIC"]["rbacadmin"]->grantPermission($member_role_id, $member_ops_ids, $ref_id);
            }

            // Change the streaming_only to -1 in order to have it tagged as processed.
            $db->manipulateF(
                "UPDATE xoct_data SET streaming_only = %s WHERE obj_id = %s",
                ['integer', 'integer'],
                [-1, $obj_id]
            );
        }

        $GLOBALS["DIC"] = $ORIG_DIC;
        return $environment;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(Environment $environment): bool
    {
        $component_repository = $environment->getResource(Setup\Environment::RESOURCE_COMPONENT_REPOSITORY);
        $info = $component_repository->getPluginByName(\ilOpenCastPlugin::PLUGIN_NAME);

        return $info->supportsCLISetup();
    }

    /**
     * Helper function to grant permission of rbac operations to a role.
     * @see $GLOBALS["DIC"]["rbacadmin"]->grantPermission()
     *
     * @param int $role_id
     * @param array $ops
     * @param int $ref_id
     */
    private function grantPermissionPureDB(int $role_id, array $ops, int $ref_id): void
    {
        $db = $GLOBALS["DIC"]["ilDB"];
        if (empty($db)) {
            return;
        }

        if ($role_id == SYSTEM_ROLE_ID) {
            return;
        }

        $ops = array_map('intval', array_unique($ops));

        $ops_ids = serialize($ops);

        // Remove first.
        $query = 'DELETE FROM rbac_pa ' .
            'WHERE rol_id = %s ' .
            'AND ref_id = %s';
        $res = $db->queryF(
            $query,
            ['integer', 'integer'],
            [$role_id, $ref_id]
        );

        if ($ops === []) {
            return;
        }

        $query = "INSERT INTO rbac_pa (rol_id,ops_id,ref_id) " .
            "VALUES " .
            "(" . $db->quote($role_id, 'integer') . "," . $db->quote(
                $ops_ids,
                'text'
            ) . "," . $db->quote($ref_id, 'integer') . ")";
        $res = $db->manipulate($query);
    }

    /**
     * Helper function to get the active operations of a role.
     * @see $GLOBALS["DIC"]["rbacreview"]->getActiveOperationsOfRole
     *
     * @param int $ref_id
     * @param int $role_id
     *
     * @return array
     */
    private function getActiveOperationsOfRolePureDB(int $ref_id, int $role_id): array
    {
        $db = $GLOBALS["DIC"]["ilDB"];
        if (empty($db)) {
            return [];
        }
        $set = $db->queryF(
            "SELECT * FROM rbac_pa WHERE ref_id = %s AND rol_id = %s",
            ["integer", "integer"],
            [$ref_id, $role_id]
        );
        while ($row = $db->fetchAssoc($set)) {
            return unserialize($row['ops_id']);
        }
        return [];
    }

    /**
     * Helper function to get default role id of an object based on the type.
     * Similar to getDefaultAdminRole, getDefaultTutorRole and getDefaultMemebrRole
     *
     * @param string $role_type
     * @param int $ref_id
     * @param string $obj_type
     *
     * @return int
     */
    private function getDefaultRoleIdForPureDB(string $role_type, int $ref_id, string $obj_type): int
    {
        $db = $GLOBALS["DIC"]["ilDB"];
        if (empty($db)) {
            return 0;
        }

        $role_key = "il_{$obj_type}_{$role_type}_{$ref_id}";

        $sql = 'SELECT rol_id FROM rbac_fa WHERE parent = %s';
        if ($obj_type == 'grp') {
            $sql .= ' AND assign = ' . $db->quote('y', 'string');
        }
        $set = $db->queryF(
            $sql,
            ["integer"],
            [$ref_id]
        );
        while ($row = $db->fetchAssoc($set)) {
            $role_id = (int) $row['rol_id'];
            $role_name = $this->lookupTitle($role_id);
            if (!strcmp($role_name, $role_key)) {
                return $role_id;
            }
        }
        return 0;
    }

    /**
     * Helper function to get ref_id and object type of the parent course/group
     *
     * @param int $ref_id
     *
     * @return array
     */
    private function getParentCourseOrGroupDataPureDB(int $ref_id): array
    {
        $db = $GLOBALS["DIC"]["ilDB"];
        if (empty($db)) {
            return [null, null];
        }

        $parent_type = $this->lookupType($ref_id, true);
        while (!in_array($parent_type, ['crs', 'grp'])) {
            if ($ref_id === 1) {
                return [null, $parent_type];
            }
            $ref_id = (int) $this->getParentId($ref_id);
            $parent_type = $this->lookupType($ref_id, true);
        }

        return [$ref_id, $parent_type];
    }

    /**
     * Helper function to lookup the title of an object
     * @see ilObject::_lookupTitle()
     *
     * @param int $id
     * @param bool $reference
     *
     * @return string
     */
    private function lookupTitlePureDB(int $id, bool $reference = false): string
    {
        $db = $GLOBALS["DIC"]["ilDB"];
        if (empty($db)) {
            return '';
        }
        $obj_id = $reference ? $this->lookupObjId($id) : $id;
        $set = $db->queryF(
            "SELECT title FROM object_data WHERE obj_id = %s",
            ["integer"],
            [$obj_id]
        );
        $rec = $db->fetchAssoc($set);
        return (string) $rec['title'];
    }

    /**
     * Helper function to lookup the type of an object
     * @see ilObject::_lookupType()
     *
     * @param int $id
     * @param bool $reference
     *
     * @return string
     */
    private function lookupTypePureDB(int $id, bool $reference = false): string
    {
        $db = $GLOBALS["DIC"]["ilDB"];
        if (empty($db)) {
            return '';
        }
        $obj_id = $reference ? $this->lookupObjId($id) : $id;
        $set = $db->queryF(
            "SELECT type FROM object_data WHERE obj_id = %s",
            ["integer"],
            [$obj_id]
        );
        $rec = $db->fetchAssoc($set);
        return (string) $rec['type'];
    }

    /**
     * Helper function to lookup the object id of an object
     * @see ilObject::_lookupObjId()
     *
     * @param int $ref_id
     *
     * @return int
     */
    private function lookupObjIdPureDB(int $ref_id): int
    {
        $db = $GLOBALS["DIC"]["ilDB"];
        if (empty($db)) {
            return 0;
        }
        $set = $db->queryF(
            "SELECT obj_id FROM object_reference WHERE ref_id = %s",
            ["integer"],
            [$ref_id]
        );
        $rec = $db->fetchAssoc($set);
        return (int) $rec['obj_id'];
    }

    /**
     * Helper function to get parent ref id in tree table
     * Similar to $GLOBALS["DIC"]->repositoryTree()->getParentId()
     *
     * @param int $child_ref_id
     *
     * @return int
     */
    private function getParentIdPureDB(int $child_ref_id): int
    {
        $db = $GLOBALS["DIC"]["ilDB"];
        if (empty($db)) {
            return 0;
        }
        $set = $db->queryF(
            "SELECT parent FROM tree WHERE child = %s",
            ["integer"],
            [$child_ref_id]
        );
        $rec = $db->fetchAssoc($set);
        return (int) $rec['parent'];
    }

    /**
     * Helper function to initialize the environment for this Objective
     *
     * @param \Setup\Environment $environment
     * @param \ilComponentRepository $component_repository
     * @param \ilComponentFactory $component_factory
     *
     * @return array old DIC values
     */
    protected function initEnvironment(
        Setup\Environment $environment,
        \ilComponentRepository $component_repository,
        \ilComponentFactory $component_factory
    ) {
        $db = $environment->getResource(Setup\Environment::RESOURCE_DATABASE);
        $plugin_admin = $environment->getResource(Setup\Environment::RESOURCE_PLUGIN_ADMIN);
        $ini = $environment->getResource(Setup\Environment::RESOURCE_ILIAS_INI);
        $client_ini = $environment->getResource(Setup\Environment::RESOURCE_CLIENT_INI);

        // ATTENTION: This is a total abomination. It only exists to allow various
        // sub components of the various readers to run. This is a memento to the
        // fact, that dependency injection is something we want. Currently, every
        // component could just service locate the whole world via the global $DIC.
        $DIC = $GLOBALS["DIC"];
        $GLOBALS["DIC"] = new DI\Container();
        $GLOBALS["DIC"]["component.repository"] = $component_repository;
        $GLOBALS["DIC"]["component.factory"] = $component_factory;
        $GLOBALS["DIC"]["ilDB"] = $db;
        $GLOBALS["DIC"]["database"] = function ($c) {
            return $GLOBALS["DIC"]["ilDB"];
        };
        $GLOBALS["DIC"]["ilIliasIniFile"] = $ini;
        $GLOBALS["DIC"]["ilClientIniFile"] = $client_ini;
        $GLOBALS["DIC"]["ilLog"] = new class () extends ilLogger {
            public function __construct()
            {
            }
            public function write(string $a_message, $a_level = ilLogLevel::INFO): void
            {
            }
            public function info(string $a_message): void
            {
            }
            public function warning(string $a_message): void
            {
            }
            public function error(string $a_message): void
            {
            }
            public function debug(string $a_message, array $a_context = []): void
            {
            }
            public function dump($a_variable, int $a_level = ilLogLevel::INFO): void
            {
            }
        };
        $GLOBALS["DIC"]["ilLoggerFactory"] = function ($c) {
            return ilLoggerFactory::getInstance();
        };
        $GLOBALS["DIC"]["ilBench"] = null;
        $GLOBALS["DIC"]["lng"] = new ilLanguage('en');
        $GLOBALS["DIC"]["ilPluginAdmin"] = $plugin_admin;
        $GLOBALS["DIC"]["ilias"] = null;
        $GLOBALS["ilLog"] = $GLOBALS["DIC"]["ilLog"];
        $GLOBALS["DIC"]["ilErr"] = null;

        $tree = new ilTree(ROOT_FOLDER_ID);
        $GLOBALS['tree'] = $tree;
        $GLOBALS["DIC"]["tree"] = function ($c) {
            return $GLOBALS["tree"];
        };

        $GLOBALS["DIC"]["ilAppEventHandler"] = new class () extends ilAppEventHandler {
            public function __construct()
            {
            }
            public function raise($a_component, $a_event, $a_parameter = ""): void
            {
            }
        };
        $GLOBALS["DIC"]["ilErr"] = new class () extends ilErrorHandling {
            public function __construct()
            {
            }
        };

        $GLOBALS["DIC"]["ilObjDataCache"] = new ilObjectDataCache();
        $GLOBALS["DIC"]["ilSetting"] = new ilSetting();
        $GLOBALS["DIC"]["ilUser"] = new class () extends ilObjUser {
            public array $prefs = [];

            public function __construct()
            {
                $this->prefs["language"] = "en";
            }
        };

        if (!defined('DEBUG')) {
            define('DEBUG', false);
        }

        if (!defined('SYSTEM_ROLE_ID')) {
            define('SYSTEM_ROLE_ID', '2');
        }

        if (!defined("ILIAS_ABSOLUTE_PATH")) {
            $absolute_path = $ini->readVariable("server", "absolute_path");
            if (empty($absolute_path)) {
                $absolute_path = dirname(__FILE__, 10);
            }
            define("ILIAS_ABSOLUTE_PATH", $absolute_path);
        }

        if (!defined("CLIENT_ID")) {
            define('CLIENT_ID', $client_ini->readVariable('client', 'name'));
        }

        if (!defined('ILIAS_DATA_DIR')) {
            define("ILIAS_DATA_DIR", $ini->readVariable("clients", "datadir"));
        }

        if (!defined("ILIAS_WEB_DIR")) {
            define('ILIAS_WEB_DIR', dirname(__DIR__, 9) . "/data/");
        }

        // initialise this last to make sure the environment defined here
        // will be available for plugins, ilObjectDefinition will create
        // plugin instances, see https://mantis.ilias.de/view.php?id=40890
        $GLOBALS["DIC"]["objDefinition"] = new ilObjectDefinition();

        $init_http = new InitHttpServices();
        $init_http->init($GLOBALS["DIC"]);
        $container = $GLOBALS["DIC"];
        $GLOBALS["DIC"]['refinery'] = function ($container) {
            $dataFactory = new \ILIAS\Data\Factory();
            $language = $container['lng'];

            return new \ILIAS\Refinery\Factory($dataFactory, $language);
        };
        $GLOBALS["DIC"]["rbacadmin"] = new class () extends ilRbacAdmin {
            public function __construct()
            {
                $this->db = $GLOBALS["DIC"]["ilDB"];
            }
        };
        $GLOBALS["DIC"]["rbacreview"] = new class () extends ilRbacReview {
            public function __construct()
            {
                $this->db = $GLOBALS["DIC"]["ilDB"];
            }
        };

        $GLOBALS["DIC"]["rbacsystem"] = ilRbacSystem::getInstance();

        $GLOBALS["DIC"]["ilAccess"] = new class () extends ilAccess {
            public function __construct()
            {
            }
        };

        \ilInitialisation::bootstrapFilesystems();

        \ilInitialisation::initFileUploadService($GLOBALS["DIC"]);

        $GLOBALS["DIC"]["contentStyle"] = new \ILIAS\Style\Content\Service($GLOBALS["DIC"]);

        return $DIC;
    }
}
