<?php

namespace srag\Plugins\Opencast\Model\PerVideoPermission;

use ActiveRecord;
use ilObject2;
use ilObjOpenCastAccess;
use ilObjUser;
use srag\Plugins\Opencast\Model\User\xoctUser;

/**
 * Class xoctIVTGroupParticipant
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class PermissionGroupParticipant extends ActiveRecord
{
    public const TABLE_NAME = 'xoct_group_participant';
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
    protected $group_id;
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
    protected static $crs_members_cache = [];

    /**
     * @param $ref_id
     * @param $group_id
     *
     * @return array
     * @throws \xoctException
     */
    public static function getAvailable($ref_id, $group_id = null)
    {
        if (isset(self::$crs_members_cache[$ref_id][$group_id])) {
            return self::$crs_members_cache[$ref_id][$group_id];
        }
        $existing = self::getAllUserIdsForOpenCastObjIdAndGroupId(ilObject2::_lookupObjId($ref_id), $group_id);

        $return = [];
        foreach (ilObjOpenCastAccess::getAllParticipants() as $user_id) {
            if (in_array($user_id, $existing)) {
                continue;
            }
            $obj = new self();
            $obj->setUserId($user_id);
            $return[] = $obj;
        }

        self::$crs_members_cache[$ref_id][$group_id] = $return;

        return $return;
    }

    /**
     * @param $obj_id
     *
     * @return array
     */
    public function getAllUserIdsForOpenCastObjId($obj_id)
    {
        $all = PermissionGroup::where(['serie_id' => $obj_id])->getArray(null, 'id');
        if (count($all) == 0) {
            return [];
        }

        return self::where(['group_id' => $all])->getArray(null, 'user_id');
    }

    /**
     * @param $obj_id
     * @param $group_id
     *
     * @return array
     */
    public static function getAllUserIdsForOpenCastObjIdAndGroupId($obj_id, $group_id)
    {
        $all = PermissionGroup::where(['serie_id' => $obj_id])->getArray(null, 'id');
        if (count($all) == 0) {
            return [];
        }

        return self::where(['group_id' => $group_id])->getArray(null, 'user_id');
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
    public function setId($id): void
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
    public function setUserId($user_id): void
    {
        $this->user_id = $user_id;
    }

    /**
     * @return int
     */
    public function getGroupId()
    {
        return $this->group_id;
    }

    /**
     * @param $group_id
     */
    public function setGroupId($group_id): void
    {
        $this->group_id = $group_id;
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
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return xoctUser
     */
    public function getXoctUser()
    {
        if (!$this->xoct_user && $this->getUserId()) {
            $this->xoct_user = xoctUser::getInstance(new ilObjUser($this->getUserId()));
        }

        return $this->xoct_user;
    }

    /**
     * @param xoctUser $xoct_user
     */
    public function setXoctUser($xoct_user): void
    {
        $this->xoct_user = $xoct_user;
    }
}
