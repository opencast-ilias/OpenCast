<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\PerVideoPermission;

use ActiveRecord;
use ilObjOpenCastAccess;
use ilObjUser;
use srag\Plugins\Opencast\Model\Event\Event;
use srag\Plugins\Opencast\Model\User\xoctUser;

/**
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class PermissionGrant extends ActiveRecord
{
    public const TABLE_NAME = 'xoct_invitations';
    public const STATUS_ACTIVE = 1;

    /**
     * @deprecated
     */
    public static function returnDbTableName(): string
    {
        return self::TABLE_NAME;
    }

    public function getConnectorContainerName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * @var int
     *
     * @con_is_primary true
     * @con_is_unique  true
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     8
     * @con_sequence   true
     */
    protected $id = 0;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     128
     */
    protected $event_identifier;
    /**
     * @var int
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     8
     */
    protected $user_id;
    /**
     * @var int
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     8
     */
    protected $owner_id;
    /**
     * @var xoctUser
     */
    protected $xoct_user;
    /**
     * @var int
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     1
     */
    protected $status = self::STATUS_ACTIVE;
    /**
     * @var array
     */
    protected static $series_id_to_groups_map = [];

    /**
     * @return PermissionGrant[]
     */
    public static function getAllInvitationsOfUser(
        string $event_identifier,
        xoctUser $xoctUser,
        bool $grant_access_rights = true
    ): array {
        $invitations = self::where([
            'user_id' => $xoctUser->getIliasUserId(),
            'event_identifier' => $event_identifier
        ])->get();

        if ($grant_access_rights) {
            return $invitations;
        }

        $active_invitations = [];
        foreach ($invitations as $inv) {
            if (ilObjOpenCastAccess::hasPermission('edit_videos', null, $inv->getOwnerId())) {
                $active_invitations[] = $inv;
            }
        }

        return $active_invitations;
    }

    /**
     * @return PermissionGrant[]|int
     */
    public static function getActiveInvitationsForEvent(
        Event $xoctEvent,
        bool $grant_access_rights = false,
        bool $count = false
    ) {
        $all_invitations = self::where([
            'event_identifier' => $xoctEvent->getIdentifier(),
        ])->get();

        // filter out users which are not part of this course/group
        $crs_participants = ilObjOpenCastAccess::getAllParticipants();
        foreach ($all_invitations as $key => $invitation) {
            if (!in_array($invitation->getUserId(), $crs_participants)) {
                unset($all_invitations[$key]);
            }
        }

        if ($grant_access_rights) {
            if ($count) {
                return count($all_invitations);
            }

            return $all_invitations;
        }

        // if grant_access_rights is deactivated, only admins' invitations are active
        $active_invitations = [];
        foreach ($all_invitations as $inv) {
            if (ilObjOpenCastAccess::hasPermission('edit_videos', null, $inv->getOwnerId())) {
                $active_invitations[] = $inv;
            }
        }

        if ($count) {
            return count($active_invitations);
        }

        return $active_invitations;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getOwnerId(): int
    {
        return $this->owner_id;
    }

    public function setOwnerId(int $owner_id): void
    {
        $this->owner_id = $owner_id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getEventIdentifier(): string
    {
        return $this->event_identifier;
    }

    public function setEventIdentifier(string $event_identifier): void
    {
        $this->event_identifier = $event_identifier;
    }
}
