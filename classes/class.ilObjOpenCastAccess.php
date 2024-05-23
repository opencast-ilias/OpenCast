<?php

declare(strict_types=1);

use srag\Plugins\Opencast\DI\OpencastDIC;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGrant;
use srag\Plugins\Opencast\Model\PerVideoPermission\PermissionGroup;
use srag\Plugins\Opencast\Model\User\xoctUser;

/**
 * Access/Condition checking for OpenCast object
 *
 * @author        Fabian Schmid <fs@studer-raimann.ch>
 *
 * @version       1.0.00
 */
class ilObjOpenCastAccess extends ilObjectPluginAccess
{
    public const ROLE_MEMBER = 1;
    public const ROLE_ADMIN = 2;
    public const ROLE_TUTOR = 3;
    public const TXT_PERMISSION_DENIED = 'permission_denied';

    public const ACTION_EDIT_OWNER = 'edit_owner';
    public const ACTION_SHARE_EVENT = 'share_event';
    public const ACTION_CUT = 'cut';
    public const ACTION_DELETE_EVENT = 'delete_event';
    public const ACTION_EDIT_EVENT = 'edit_event';
    public const ACTION_SET_ONLINE_OFFLINE = 'set_online_offline';
    public const ACTION_ADD_EVENT = 'add_event';
    public const ACTION_MANAGE_IVT_GROUPS = 'manage_ivt_groups';
    public const ACTION_EDIT_SETTINGS = 'edit_settings';
    public const ACTION_EXPORT_CSV = 'export_csv';
    public const ACTION_REPORT_QUALITY_PROBLEM = 'report_quality_problem';
    public const ACTION_REPORT_DATE_CHANGE = 'report_date_change';
    public const ACTION_VIEW_UNPROTECTED_LINK = 'unprotected_link';
    public const ACTION_DOWNLOAD_EVENT = 'download_event';
    public const ACTION_SCHEDULE_EVENT = 'schedule_event';
    public const ACTION_RECORD_EVENT = 'record_event';

    public const PERMISSION_EDIT_VIDEOS = 'edit_videos';
    public const PERMISSION_UPLOAD = 'upload';
    public const PERMISSION_DOWNLOAD = 'download';
    public const PERMISSION_RECORD = 'record';
    public const PERMISSION_SCHEDULE = 'schedule';

    /**
     * @var array
     */
    protected static $custom_rights = [
        self::PERMISSION_UPLOAD,
        self::PERMISSION_EDIT_VIDEOS,
        self::PERMISSION_DOWNLOAD,
        self::PERMISSION_RECORD,
        self::PERMISSION_SCHEDULE,
    ];
    /**
     * @var array
     */
    protected static $cache = [];
    /**
     * @var array
     */
    protected static $members = [];
    /**
     * @var array
     */
    protected static $members_allowed_defaults = [
        'visible',
        'read'
    ];
    /**
     * @var array
     */
    protected static $tutors = [];
    /**
     * @var array
     */
    protected static $tutors_allowed_defaults = [
        'visible',
        'read',
        'write',
        self::PERMISSION_UPLOAD,
        self::PERMISSION_RECORD,
        self::PERMISSION_EDIT_VIDEOS,
        self::PERMISSION_DOWNLOAD
    ];
    /**
     * @var array
     */
    protected static $admins = [];
    /**
     * @var array
     */
    protected static $admins_allowed_defaults = [
        'visible',
        'read',
        'write',
        'delete',
        // 'copy',
        'edit_permission',
        self::PERMISSION_UPLOAD,
        self::PERMISSION_RECORD,
        self::PERMISSION_EDIT_VIDEOS,
        self::PERMISSION_DOWNLOAD
    ];


    /**
     * @param string $a_cmd
     * @param string $a_permission
     * @param int    $a_ref_id
     * @param int    $a_obj_id
     * @param string $a_user_id
     */
    public function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id = null, $a_user_id = ''): bool
    {
        if (empty($a_user_id)) {
            $a_user_id = $this->user->getId();
        }
        if ($a_obj_id === null) {
            $a_obj_id = ilObject2::_lookupObjId($a_ref_id);
        }

        $a_obj_id = (int) $a_obj_id;

        switch ($a_permission) {
            case 'read':
            case 'visible':
                if (!ilObjOpenCastAccess::checkOnline($a_obj_id) && !$this->access->checkAccessOfUser(
                        $a_user_id,
                        'write',
                        '',
                        $a_ref_id
                    )) {
                    return false;
                }
                break;
        }

        return true;
    }

    protected static function redirectNonAccess()
    {
        global $DIC;
        $main_tpl = $DIC->ui()->mainTemplate();
        $ctrl = $DIC->ctrl();
        $main_tpl->setOnScreenMessage(
            'failure', ilOpenCastPlugin::getInstance()->txt(self::TXT_PERMISSION_DENIED), true
        );
        $ctrl->redirectByClass('ilRepositoryGUI');
    }

    public static function checkOnline(int $a_id): bool
    {
        return ObjectSettings::findOrGetInstance($a_id)->isOnline();
    }

    public static function hasWriteAccess(int $ref_id = null): bool
    {
        global $DIC;
        $access = $DIC->access();
        $ref_id = $ref_id ?? (int) ($DIC->http()->request()->getQueryParams()['ref_id'] ?? 0);

        return $access->checkAccess('write', '', $ref_id);
    }

    public static function checkAction(
        string $cmd,
        Event $event = null,
        xoctUser $user = null,
        ObjectSettings $objectSettings = null,
        ?int $ref_id = null
    ): bool {
        global $DIC;

        if (!$user instanceof \srag\Plugins\Opencast\Model\User\xoctUser) {
            $user = xoctUser::getInstance($DIC->user());
        }

        $ref_id = $ref_id ?? (int) ($DIC->http()->request()->getQueryParams()['ref_id'] ?? 0);

        $opencastDIC = OpencastDIC::getInstance();

        switch ($cmd) {
            case self::ACTION_EDIT_OWNER:
                return
                    self::hasPermission(self::PERMISSION_EDIT_VIDEOS, $ref_id)
                    && $event->getProcessingState() != Event::STATE_ENCODING
                    && ilObjOpenCast::_getParentCourseOrGroup($ref_id)
                    && $objectSettings->getPermissionPerClip();
            case self::ACTION_SHARE_EVENT:
                return
                    (self::hasPermission(
                            self::PERMISSION_EDIT_VIDEOS,
                            $ref_id
                        ) && $objectSettings->getPermissionPerClip())
                    || ($opencastDIC->acl_utils()->isUserOwnerOfEvent($user, $event)
                        && $objectSettings->getPermissionAllowSetOwn()
                        && $event->getProcessingState() != Event::STATE_ENCODING
                        && $event->getProcessingState() != Event::STATE_FAILED);
            case self::ACTION_CUT:
                return
                    self::hasPermission(self::PERMISSION_EDIT_VIDEOS, $ref_id)
                    && $event->hasPreviews()
                    && $event->getProcessingState() != Event::STATE_FAILED;
            case self::ACTION_DELETE_EVENT:
                return
                    (self::hasPermission(self::PERMISSION_EDIT_VIDEOS)
                        || (self::hasPermission(self::PERMISSION_UPLOAD)
                            && $opencastDIC->acl_utils()->isUserOwnerOfEvent($user, $event)))
                    && $event->getProcessingState() != Event::STATE_ENCODING;
            case self::ACTION_EDIT_EVENT:
                return
                    (self::hasPermission(self::PERMISSION_EDIT_VIDEOS)
                        || (self::hasPermission(self::PERMISSION_UPLOAD)
                            && $opencastDIC->acl_utils()->isUserOwnerOfEvent($user, $event)))
                    && $event->getProcessingState() != Event::STATE_ENCODING
                    && $event->getProcessingState() != Event::STATE_FAILED
                    && (!$event->isScheduled() || PluginConfig::getConfig(
                            PluginConfig::F_SCHEDULED_METADATA_EDITABLE
                        ) != PluginConfig::NO_METADATA);
            case self::ACTION_SET_ONLINE_OFFLINE:
                return
                    self::hasPermission(self::PERMISSION_EDIT_VIDEOS)
                    && $event->getProcessingState() != Event::STATE_ENCODING
                    && $event->getProcessingState() != Event::STATE_FAILED;
            case self::ACTION_ADD_EVENT:
                return
                    self::hasPermission(self::PERMISSION_UPLOAD);
            case self::ACTION_EXPORT_CSV:
            case self::ACTION_VIEW_UNPROTECTED_LINK:
            case self::ACTION_MANAGE_IVT_GROUPS:
                return
                    self::hasPermission(self::PERMISSION_EDIT_VIDEOS);
            case self::ACTION_EDIT_SETTINGS:
                return
                    self::hasWriteAccess(); // = permission: 'edit settings'
            case self::ACTION_REPORT_QUALITY_PROBLEM:
                return
                    PluginConfig::getConfig(PluginConfig::F_REPORT_QUALITY)
                    && ((PluginConfig::getConfig(PluginConfig::F_REPORT_QUALITY_ACCESS) == PluginConfig::ACCESS_ALL)
                        || self::hasPermission(self::PERMISSION_EDIT_VIDEOS)
                        || $opencastDIC->acl_utils()->isUserOwnerOfEvent($user, $event));
            case self::ACTION_REPORT_DATE_CHANGE:
                return
                    PluginConfig::getConfig(PluginConfig::F_REPORT_DATE) && self::hasPermission(
                        self::PERMISSION_EDIT_VIDEOS
                    );
            case self::ACTION_DOWNLOAD_EVENT:
                return
                    ((self::hasPermission(self::PERMISSION_DOWNLOAD) && self::hasReadAccessOnEvent($event, $user, $objectSettings))
                        || (/* (self::hasPermission(self::PERMISSION_DOWNLOAD) || self::hasPermission(self::PERMISSION_UPLOAD)) && */
                            $opencastDIC->acl_utils()->isUserOwnerOfEvent($user, $event)))
                    && $event->getProcessingState() == Event::STATE_SUCCEEDED;
            case self::ACTION_SCHEDULE_EVENT:
                return
                    self::hasPermission(self::PERMISSION_SCHEDULE);
            case self::ACTION_RECORD_EVENT:
                return
                    self::hasPermission(self::PERMISSION_RECORD);
            default:
                return false;
        }
    }

    public static function hasPermission(string $permission, ?int $ref_id = null, ?int $usr_id = null)
    {
        global $DIC;
        $access = $DIC->access();
        $ref_id = $ref_id ?? (int) ($DIC->http()->request()->getQueryParams()['ref_id'] ?? 0);

        $prefix = in_array($permission, self::$custom_rights) ? "rep_robj_xoct_perm_" : "";
        if ($usr_id === null) {
            return $access->checkAccess($prefix . $permission, '', $ref_id);
        }
        return $access->checkAccessOfUser($usr_id, $prefix . $permission, '', $ref_id);
    }

    /**
     * @throws xoctException
     */
    public static function hasReadAccessOnEvent(Event $event, xoctUser $xoctUser, ObjectSettings $objectSettings): bool
    {
        // edit_videos see all videos
        if (ilObjOpenCastAccess::hasPermission(self::PERMISSION_EDIT_VIDEOS)) {
            return true;
        }

        $opencastDIC = OpencastDIC::getInstance();
        // owner can see failed videos
        if ($event->getProcessingState() == $event::STATE_FAILED) {
            return $opencastDIC->acl_utils()->isUserOwnerOfEvent($xoctUser, $event)
                && ($objectSettings->getPermissionPerClip()
                    || self::hasPermission(self::PERMISSION_UPLOAD));
        }

        // don't show offline and failed videos
        if (!$event->getXoctEventAdditions()->getIsOnline()) {
            return false;
        }

        // if owner, show video
        if ($opencastDIC->acl_utils()->isUserOwnerOfEvent($xoctUser, $event)) {
            return true;
        }

        // if not owner or edit_videos, don't show proceeding videos
        if (!(in_array(
            $event->getProcessingState(),
            [Event::STATE_SUCCEEDED, Event::STATE_LIVE_SCHEDULED, Event::STATE_LIVE_RUNNING]
        ))) {
            return false;
        }

        // If write access, unpublished videos for others are not allowed!
        // Here the place where to check the write access is very important, and it must come after the event state check!
        if (ilObjOpenCastAccess::hasWriteAccess()) {
            return true;
        }

        // no ivt mode: show residual videos
        if (!$objectSettings->getPermissionPerClip()) {
            return true;
        }

        $xoctGroupParticipants = PermissionGroup::getAllGroupParticipantsOfUser(
            $event->getSeriesIdentifier(),
            $xoctUser
        );
        foreach ($xoctGroupParticipants as $xoctGroupParticipant) {
            if ($opencastDIC->acl_utils()->isUserOwnerOfEvent($xoctGroupParticipant->getXoctUser(), $event)) {
                return true;
            }
        }

        $invitations = PermissionGrant::getAllInvitationsOfUser(
            $event->getIdentifier(),
            $xoctUser,
            $objectSettings->getPermissionAllowSetOwn()
        );
        return $invitations !== [];
    }

    protected static function initRoleMembers(): void
    {
        global $DIC;
        static $init;
        if ($init) {
            return;
        }
        $ref_id = (int) ($DIC->http()->request()->getQueryParams()['ref_id'] ?? 0);

        $crs_or_grp_obj = ilObjOpenCast::_getParentCourseOrGroup($ref_id);
        $roles = ($crs_or_grp_obj instanceof ilObjCourse) ? ['admin', 'tutor', 'member'] : ['admin', 'member'];
        foreach ($roles as $role) {
            $getter_method = "getDefault{$role}Role";
            $role_id = $crs_or_grp_obj->$getter_method();
            $participants = $DIC->rbac()->review()->assignedUsers($role_id);
            $setter_method = "set{$role}s";
            self::$setter_method($participants);
        }

        $init = true;
    }

    public static function isActionAllowedForRole(string $action, string $role, ?int $ref_id = null): bool
    {
        global $DIC;
        $ref_id = $ref_id ?? (int) ($DIC->http()->request()->getQueryParams()['ref_id'] ?? 0);
        $prefix = in_array($action, self::$custom_rights) ? "rep_robj_xoct_perm_" : "";
        if (!$parent_obj = ilObjOpenCast::_getParentCourseOrGroup($ref_id)) {
            return false;
        }
        $fetch_role_method = "getDefault{$role}Role";
        $active_operations = $DIC->rbac()->review()->getActiveOperationsOfRole(
            $ref_id,
            $parent_obj->$fetch_role_method()
        );
        foreach ($active_operations as $op_id) {
            $operation = $DIC->rbac()->review()->getOperation($op_id);
            if ($operation['operation'] == $prefix . $action) {
                return true;
            }
        }
        return false;
    }

    /**
     * returns array of xoctUsers who have the permission 'edit_videos' in this context
     * @return xoctUser[]
     */
    public static function getProducersForRefID(int $ref_id): array
    {
        global $DIC;
        $producers = [];
        if ($crs_or_grp_obj = ilObjOpenCast::_getParentCourseOrGroup($ref_id)) {
            //check each role (admin,tutor,member) for perm edit_videos, add to producers
            $roles = ($crs_or_grp_obj instanceof ilObjCourse) ? ['admin', 'tutor', 'member'] : ['admin', 'member'];
            foreach ($roles as $role) {
                if (self::isActionAllowedForRole(self::PERMISSION_EDIT_VIDEOS, $role, $ref_id)) {
                    $getter_method = "getDefault{$role}Role";
                    $role_id = $crs_or_grp_obj->$getter_method();
                    foreach ($DIC->rbac()->review()->assignedUsers($role_id) as $participant_id) {
                        $producers[] = xoctUser::getInstance($participant_id);
                    }
                }
            }
        }
        return $producers;
    }

    /**
     * used at object creation
     */
    public static function activateMemberUpload(int $ref_id): void
    {
        global $DIC;
        $parent_obj = ilObjOpenCast::_getParentCourseOrGroup($ref_id);
        $member_role_id = $parent_obj->getDefaultMemberRole();
        $ops_id_upload = $DIC->rbac()->review()->_getOperationIdByName('rep_robj_xoct_perm_upload');
        $ops_ids = $DIC->rbac()->review()->getActiveOperationsOfRole($ref_id, $member_role_id);
        $ops_ids[] = $ops_id_upload;
        $DIC->rbac()->admin()->grantPermission($member_role_id, $ops_ids, $ref_id);
    }

    public static function getParentId($get_ref_id = false, int $ref_id = null): ?int
    {
        global $DIC;
        $parent_id = null;
        $for_ref_id = $ref_id = $ref_id ?? (int) ($DIC->http()->request()->getQueryParams()['ref_id'] ?? 0);
        foreach ($DIC->repositoryTree()->getNodePath($for_ref_id) as $node) {
            if ($node['type'] ?? '' === 'crs' || $node['type'] ?? '' === 'grp') {
                $parent_id = $node[$get_ref_id ? 'child' : 'obj_id'];
                break;
            }
        }

        return $parent_id;
    }

    public static function getAllParticipants(): array
    {
        return array_map('intval', array_merge(self::getMembers(), self::getTutors(), self::getAdmins()));
    }

    /**
     * @return array
     */
    public static function getMembers()
    {
        self::initRoleMembers();

        return self::$members;
    }

    /**
     * @param array $members
     */
    public static function setMembers($members): void
    {
        self::$members = $members;
    }

    /**
     * @return array
     */
    public static function getTutors()
    {
        self::initRoleMembers();

        return self::$tutors;
    }

    /**
     * @param array $tutors
     */
    public static function setTutors($tutors): void
    {
        self::$tutors = $tutors;
    }

    /**
     * @return array
     */
    public static function getAdmins()
    {
        self::initRoleMembers();

        return self::$admins;
    }

    /**
     * @param array $admins
     */
    public static function setAdmins($admins): void
    {
        self::$admins = $admins;
    }

    /**
     * Applies the default perms upon creating a new series object.
     *
     * @param int $ref_id Ref id
     * @param array $additionals possiblity to pass additional perms upon creating which could be configured by admins in settings.
     *                  possible values:
     *                      [
     *                          [self::ROLE_MEMBER] => [{array list of additional perms}],
     *                          [self::ROLE_TUTOR] => [{array list of additional perms}],
     *                          [self::ROLE_ADMIN] => [{array list of additional perms}],
     *                      ]
     */
    public static function applyDefaultPerms(int $ref_id, array $additionals = []): void
    {
        $parent_obj = ilObjOpenCast::_getParentCourseOrGroup($ref_id);

        // Admin defaults.
        $admin_role_id = $parent_obj->getDefaultAdminRole();
        $admins_allowed_defaults_perms = self::$admins_allowed_defaults ?? [];
        if (!empty($additionals[self::ROLE_ADMIN])) {
            $admins_allowed_defaults_perms = array_unique(
                array_merge($admins_allowed_defaults_perms, $additionals[self::ROLE_ADMIN])
            );
        }
        self::setDefaultPerms($ref_id, $admin_role_id, $admins_allowed_defaults_perms);

        // Tutor defaults.
        $tutor_role_id = $parent_obj->getDefaultTutorRole();
        $tutors_allowed_defaults_perms = self::$tutors_allowed_defaults ?? [];
        if (!empty($additionals[self::ROLE_TUTOR])) {
            $tutors_allowed_defaults_perms = array_unique(
                array_merge($tutors_allowed_defaults_perms, $additionals[self::ROLE_TUTOR])
            );
        }
        self::setDefaultPerms($ref_id, $tutor_role_id, $tutors_allowed_defaults_perms);

        // Member defaults.
        $member_role_id = $parent_obj->getDefaultMemberRole();
        $members_allowed_defaults_perms = self::$members_allowed_defaults ?? [];
        if (!empty($additionals[self::ROLE_MEMBER])) {
            $members_allowed_defaults_perms = array_unique(
                array_merge($members_allowed_defaults_perms, $additionals[self::ROLE_MEMBER])
            );
        }
        self::setDefaultPerms($ref_id, $member_role_id, $members_allowed_defaults_perms);
    }

    /**
     * Set default permissions.
     *
     * @param int $ref_id ref id
     * @param int $role_id role id
     * @param array $perms_to_allow array list of perms to allow by default
     */
    private static function setDefaultPerms(int $ref_id, int $role_id, array $perms_to_allow): void
    {
        global $DIC;
        $ops_ids = $DIC->rbac()->review()->getActiveOperationsOfRole($ref_id, $role_id) ?? [];
        foreach ($perms_to_allow as $allowed_perm) {
            $prefix = in_array($allowed_perm, self::$custom_rights) ? "rep_robj_xoct_perm_" : "";
            $perm_name = $prefix . $allowed_perm;
            $allowed_ops_id = $DIC->rbac()->review()->_getOperationIdByName($perm_name);
            if (!in_array($allowed_ops_id, $ops_ids)) {
                $ops_ids[] = $allowed_ops_id;
            }
        }
        $DIC->rbac()->admin()->grantPermission($role_id, $ops_ids, $ref_id);
    }
}
