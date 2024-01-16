<?php

declare(strict_types=1);

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
     * @var xoctUser|null
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
    protected static $crs_members_cache = [];

    /**
     * @return PermissionGroupParticipant[]
     */
    public static function getAvailable(int $ref_id, ?int $group_id = null): array
    {
        if (isset(self::$crs_members_cache[$ref_id][$group_id])) {
            return self::$crs_members_cache[$ref_id][$group_id];
        }
        $existing = self::getAllUserIdsForOpenCastObjIdAndGroupId(ilObject2::_lookupObjId($ref_id), $group_id);

        $return = [];
        foreach (ilObjOpenCastAccess::getAllParticipants() as $user_id) {
            if (in_array($user_id, $existing, true)) {
                continue;
            }
            $obj = new self();
            $obj->setUserId($user_id);
            $return[] = $obj;
        }

        self::$crs_members_cache[$ref_id][$group_id] = $return;

        return $return;
    }

    public function getAllUserIdsForOpenCastObjId(int $obj_id): array
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
    public static function getAllUserIdsForOpenCastObjIdAndGroupId(int $obj_id, int $group_id): array
    {
        $all = PermissionGroup::where(['serie_id' => $obj_id])->getArray(null, 'id');
        if (count($all) === 0) {
            return [];
        }

        return self::where(['group_id' => $group_id])->getArray(null, 'user_id');
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getUserId(): int
    {
        return (int) $this->user_id;
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getGroupId(): int
    {
        return (int) $this->group_id;
    }

    public function setGroupId(int $group_id): void
    {
        $this->group_id = $group_id;
    }

    public function getStatus(): int
    {
        return (int) $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getXoctUser(): ?xoctUser
    {
        if (!$this->xoct_user && $this->getUserId()) {
            $this->xoct_user = xoctUser::getInstance(new ilObjUser((int) $this->getUserId()));
        }

        return $this->xoct_user;
    }

    public function setXoctUser(xoctUser $xoct_user): void
    {
        $this->xoct_user = $xoct_user;
    }
}
