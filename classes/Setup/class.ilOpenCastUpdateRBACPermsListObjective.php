<?php

declare(strict_types=1);

use ILIAS\Setup\NoConfirmationException;
use ILIAS\DI\Container;
use ILIAS\Setup\Environment;
use ILIAS\Setup\NullConfig;

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
        $component_repository = $environment->getResource(Environment::RESOURCE_COMPONENT_REPOSITORY);
        $info = $component_repository->getPluginByName(ilOpenCastPlugin::PLUGIN_NAME);

        if (!$info->supportsCLISetup()) {
            throw new \RuntimeException(
                "OpenCast plugin does not support command line setup."
            );
        }

        $admin_interaction = $environment->getResource(Environment::RESOURCE_ADMIN_INTERACTION);

        $message =
            "You are about to perform an update action on RBAC record sets of the current OpenCast objects.\n" .
            "This is considered as a risky action, because in case of any error,\n" .
            "the action and the data is irreversible. Are you sure you would like to perform this action?.\n";

        if (!$admin_interaction->confirmOrDeny($message)) {
            throw new NoConfirmationException($message);
        }

        $ORIG_DIC = $this->initEnvironment($environment);


        global $DIC;
        $edit_settings_op_id = ilRbacReview::_getCustomRBACOperationId("write");
        // $copy_op_id = ilRbacReview::_getCustomRBACOperationId("copy");

        $upload_op_id = ilRbacReview::_getCustomRBACOperationId("rep_robj_xoct_perm_upload");
        $edit_videos_op_id = ilRbacReview::_getCustomRBACOperationId("rep_robj_xoct_perm_edit_videos");

        $download_op_id = ilRbacReview::_getCustomRBACOperationId("rep_robj_xoct_perm_download");
        $record_op_id = ilRbacReview::_getCustomRBACOperationId("rep_robj_xoct_perm_record");
        $schedule_op_id = ilRbacReview::_getCustomRBACOperationId("rep_robj_xoct_perm_schedule");

        // Get all current opencast object records.
        $db = $DIC->database();
        $set = $db->query(
            'SELECT xoct_data.obj_id, xoct_data.streaming_only, object_reference.ref_id FROM xoct_data
            INNER JOIN object_reference ON object_reference.obj_id = xoct_data.obj_id',
        );
        while ($row = $db->fetchAssoc($set)) {
            $obj_id = (int) $row["obj_id"];
            $ref_id = (int) $row["ref_id"];
            $no_download = (bool) $row["streaming_only"];

            // determine crs or grp parent
            $parent_ref_id = $ref_id;
            $parent_type = null;
            do {
                $parent_node = $DIC->repositoryTree()->getParentNodeData($parent_ref_id);
                $parent_type = $parent_node["type"];
                $parent_ref_id = $parent_node["ref_id"] ?? null;
            } while ($parent_type !== 'crs' && $parent_type !== 'grp' && $parent_ref_id !== null);

            if ($parent_ref_id === null) {
                // no crs or grp parent found, skip
                continue;
            }

            $role_arr = $DIC->rbac()->review()->getRolesOfRoleFolder($parent_ref_id, false);

            $roles = [];

            foreach ($role_arr as $role_id) {
                $role_name = $this->lookupTitlePureDB($role_id, false);

                preg_match(
                    '/il_' . $parent_type . '_(admin|tutor|member)_' . $parent_ref_id . '/',
                    $role_name,
                    $matches
                );
                $simple_role_name = $matches[1] ?? $role_name;
                $roles[$simple_role_name] = $role_id;
            }

            // Admins perms.
            if (isset($roles['admin'])) {
                $admin_role_id = (int) $roles['admin'];
                $admin_ops_ids = $DIC->rbac()->review()->getActiveOperationsOfRole($ref_id, $admin_role_id);

                // Take care of download.
                if (!$no_download && !in_array(
                    $download_op_id,
                    $admin_ops_ids
                )) { // download allowed, add the download op id
                    $admin_ops_ids[] = $download_op_id;
                } elseif ($no_download && in_array(
                    $download_op_id,
                    $admin_ops_ids
                )) { // download NOT allowed, remove download op
                    unset($admin_ops_ids[array_search($download_op_id, $admin_ops_ids)]);
                }

                if (in_array($edit_videos_op_id, $admin_ops_ids)) {
                    $admin_ops_ids[] = $upload_op_id;
                    $admin_ops_ids[] = $record_op_id;
                    $admin_ops_ids[] = $schedule_op_id;
                } elseif (in_array($upload_op_id, $admin_ops_ids)) {
                    $admin_ops_ids[] = $record_op_id;
                    // Taking out Schedule if by any change is there, that is unlikely if this Objective is freshly achieved!
                    if (in_array($schedule_op_id, $admin_ops_ids)) {
                        unset($admin_ops_ids[array_search($schedule_op_id, $admin_ops_ids)]);
                    }
                } elseif (in_array($edit_settings_op_id, $admin_ops_ids)) {
                    if (in_array($edit_videos_op_id, $admin_ops_ids)) {
                        unset($admin_ops_ids[array_search($edit_videos_op_id, $admin_ops_ids)]);
                    }
                    // Taking out Schedule if by any change is there, that is unlikely if this Objective is freshly achieved!
                    if (in_array($schedule_op_id, $admin_ops_ids)) {
                        unset($admin_ops_ids[array_search($schedule_op_id, $admin_ops_ids)]);
                    }
                }

                $admin_ops_ids = array_map('intval', array_unique($admin_ops_ids));
                $DIC->rbac()->admin()->grantPermission($admin_role_id, $admin_ops_ids, $ref_id);
            }

            // Tutor perms.
            if (isset($roles['tutor'])) {
                $tutor_role_id = (int) $roles['tutor'];
                $tutor_ops_ids = $DIC->rbac()->review()->getActiveOperationsOfRole($ref_id, $tutor_role_id);

                // Take care of download.
                if (!$no_download && !in_array(
                    $download_op_id,
                    $tutor_ops_ids
                )) { // download allowed, add the download op id
                    $tutor_ops_ids[] = $download_op_id;
                } elseif ($no_download && in_array(
                    $download_op_id,
                    $tutor_ops_ids
                )) { // download NOT allowed, remove download op
                    unset($tutor_ops_ids[array_search($download_op_id, $tutor_ops_ids)]);
                }

                if (in_array($edit_videos_op_id, $tutor_ops_ids)) {
                    $tutor_ops_ids[] = $upload_op_id;
                    $tutor_ops_ids[] = $record_op_id;
                    $tutor_ops_ids[] = $schedule_op_id;
                } elseif (in_array($upload_op_id, $tutor_ops_ids)) {
                    $tutor_ops_ids[] = $record_op_id;
                    // Taking out Schedule if by any change is there, that is unlikely if this Objective is freshly achieved!
                    if (in_array($schedule_op_id, $tutor_ops_ids)) {
                        unset($tutor_ops_ids[array_search($schedule_op_id, $tutor_ops_ids)]);
                    }
                } elseif (in_array($edit_settings_op_id, $tutor_ops_ids)) {
                    if (in_array($edit_videos_op_id, $tutor_ops_ids)) {
                        unset($tutor_ops_ids[array_search($edit_videos_op_id, $tutor_ops_ids)]);
                    }
                    // Taking out Schedule if by any change is there, that is unlikely if this Objective is freshly achieved!
                    if (in_array($schedule_op_id, $tutor_ops_ids)) {
                        unset($tutor_ops_ids[array_search($schedule_op_id, $tutor_ops_ids)]);
                    }
                }

                $tutor_ops_ids = array_map('intval', array_unique($tutor_ops_ids));
                $DIC->rbac()->admin()->grantPermission($tutor_role_id, $tutor_ops_ids, $ref_id);
            }

            // Member perms.
            if (isset($roles['member'])) {
                $member_role_id = (int) $roles['member'];
                $member_ops_ids = $DIC->rbac()->review()->getActiveOperationsOfRole($ref_id, $member_role_id);

                // Take care of download.
                if (!$no_download && !in_array(
                    $download_op_id,
                    $member_ops_ids
                )) { // download allowed, add the download op id
                    $member_ops_ids[] = $download_op_id;
                } elseif ($no_download && in_array(
                    $download_op_id,
                    $member_ops_ids
                )) { // download NOT allowed, remove download op
                    unset($member_ops_ids[array_search($download_op_id, $member_ops_ids)]);
                }

                if (in_array($edit_videos_op_id, $member_ops_ids)) {
                    $member_ops_ids[] = $upload_op_id;
                    $member_ops_ids[] = $record_op_id;
                    $member_ops_ids[] = $schedule_op_id;
                } elseif (in_array($upload_op_id, $member_ops_ids)) {
                    $member_ops_ids[] = $record_op_id;
                    // Taking out Schedule if by any change is there, that is unlikely if this Objective is freshly achieved!
                    if (in_array($schedule_op_id, $member_ops_ids)) {
                        unset($member_ops_ids[array_search($schedule_op_id, $member_ops_ids)]);
                    }
                } elseif (in_array($edit_settings_op_id, $member_ops_ids)) {
                    if (in_array($edit_videos_op_id, $member_ops_ids)) {
                        unset($member_ops_ids[array_search($edit_videos_op_id, $member_ops_ids)]);
                    }
                    // Taking out Schedule if by any change is there, that is unlikely if this Objective is freshly achieved!
                    if (in_array($schedule_op_id, $member_ops_ids)) {
                        unset($member_ops_ids[array_search($schedule_op_id, $member_ops_ids)]);
                    }
                }

                $member_ops_ids = array_map('intval', array_unique($member_ops_ids));
                $DIC->rbac()->admin()->grantPermission($member_role_id, $member_ops_ids, $ref_id);
            }

            // Change the streaming_only to -1 in order to have it tagged as processed.
            $db->manipulateF(
                "UPDATE xoct_data SET streaming_only = %s WHERE obj_id = %s",
                ['integer', 'integer'],
                [-1, $obj_id]
            );
        }

        // reset DIC back to original
        $DIC = $ORIG_DIC;

        return $environment;
    }

    /**
     * @inheritDoc
     */
    public function isApplicable(Environment $environment): bool
    {
        $component_repository = $environment->getResource(Environment::RESOURCE_COMPONENT_REPOSITORY);
        return $component_repository->getPluginByName(\ilOpenCastPlugin::PLUGIN_NAME)->supportsCLISetup();
    }

    /**
     * Helper function to lookup the title of an object
     *
     * @see ilObject::_lookupTitle()
     *
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
     * Helper function to initialize the environment for this Objective
     *
     * @param \ilComponentRepository $component_repository
     * @param \ilComponentFactory    $component_factory
     * @return array old DIC values
     */
    protected function initEnvironment(
        Environment $environment
    ): Container|array {
        $db = $environment->getResource(Environment::RESOURCE_DATABASE);
        $client_ini = $environment->getResource(Environment::RESOURCE_CLIENT_INI);
        $component_repository = $environment->getResource(Environment::RESOURCE_COMPONENT_REPOSITORY);
        $component_factory = $environment->getResource(Environment::RESOURCE_COMPONENT_FACTORY);

        global $DIC;
        $ORIGINAL_DIC = $DIC;

        $DIC = $DIC instanceof Container ? $DIC : new Container();
        $db = $environment->getResource(Environment::RESOURCE_DATABASE);
        $DIC["ilDB"] = $db;
        $DIC['rbacreview'] = fn($c): \ilRbacReview => new class () extends ilRbacReview {
            /** @noinspection MagicMethodsValidityInspection */
            public function __construct()
            {
                $this->db = $GLOBALS["DIC"]["ilDB"];
            }
        };
        $DIC['rbacadmin'] = fn($c): \ilRbacAdmin => new class () extends ilRbacAdmin {
            /** @noinspection MagicMethodsValidityInspection */
            public function __construct()
            {
                $this->db = $GLOBALS["DIC"]["ilDB"];
            }
        };
        $DIC['tree'] = fn($c): \ilTree => new ilTree(1);
        $DIC['ilLog'] = fn($c): \ilLogger => ilLoggerFactory::getInstance()->getLogger('setup');
        $DIC['objDefinition'] = fn($c): \ilObjectDefinition => new ilObjectDefinition();
        $DIC['component.repository'] = fn($c) => $component_repository;
        $DIC['component.factory'] = fn($c) => $component_factory;
        $DIC['ilSetting'] = fn($c): \ilSetting => new ilSetting();
        $DIC['lng'] = fn($c): \ilLanguage => new ilLanguage('en');
        $DIC['ilClientIniFile'] = fn($c) => $client_ini;
        $DIC['ilLoggerFactory'] = fn($c): \ilLoggerFactory => ilLoggerFactory::getInstance();
        if (!defined('SYSTEM_ROLE_ID')) {
            define('SYSTEM_ROLE_ID', '2');
        }

        return $ORIGINAL_DIC;
    }
}
