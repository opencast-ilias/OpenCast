<?php

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
     * @return string
     * @deprecated
     */
    public static function returnDbTableName()
    {
        return self::TABLE_NAME;
    }


    /**
     * @return string
     */
    public function getConnectorContainerName()
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
    protected $xoct_user = null;
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
     * @param          $event_identifier
     * @param xoctUser $xoctUser
     *
     * @return array
     */
    public static function getAllInvitationsOfUser($event_identifier, xoctUser $xoctUser, $grant_access_rights = true)
    {
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
     * @param Event $xoctEvent
     * @param bool $grant_access_rights
     * @param bool $count
     *
     * @return mixed
     */
    public static function getActiveInvitationsForEvent(Event $xoctEvent, $grant_access_rights = false, $count = false)
    {
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


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }


    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }


    /**
     * @param int $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }


    /**
     * @return int
     */
    public function getOwnerId()
    {
        return $this->owner_id;
    }


    /**
     * @param int $owner_id
     */
    public function setOwnerId($owner_id)
    {
        $this->owner_id = $owner_id;
    }


    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }


    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }


    /**
     * @return int
     */
    public function getEventIdentifier()
    {
        return $this->event_identifier;
    }


    /**
     * @param int $event_identifier
     */
    public function setEventIdentifier($event_identifier)
    {
        $this->event_identifier = $event_identifier;
    }


    /**
     * @return xoctUser
     */
    public function getXoctUser()
    {
        if (!$this->xoct_user and $this->getUserId()) {
            $this->xoct_user = xoctUser::getInstance(new ilObjUser($this->getUserId()));
        }

        return $this->xoct_user;
    }


    /**
     * @param xoctUser $xoct_user
     */
    public function setXoctUser($xoct_user)
    {
        $this->xoct_user = $xoct_user;
    }
}
