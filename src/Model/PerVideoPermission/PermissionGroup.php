<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\PerVideoPermission;

use ActiveRecord;
use ilObject;
use ilObject2;
use srag\Plugins\Opencast\Model\Object\ObjectSettings;
use srag\Plugins\Opencast\Model\User\xoctUser;

/**
 * Class xoctIVTGroup
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class PermissionGroup extends ActiveRecord
{
    public const TABLE_NAME = 'xoct_group';

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
     * @return PermissionGroup[]
     */
    public static function getAllForId(int $id, bool $call_by_reference = false): array
    {
        if ($call_by_reference) {
            $id = ilObject::_lookupObjectId($id);
        }

        return self::where(['serie_id' => $id])->orderBy('title')->get();
    }

    /**
     * @var array
     */
    protected static $series_id_to_groups_map = [];
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
     * @return PermissionGroupParticipant[]
     */
    public static function getAllGroupParticipantsOfUser(string $series_identifier, xoctUser $xoctUser): array
    {
        self::loadGroupIdsForSeriesId($series_identifier);
        $group_ids = self::$series_id_to_groups_map[$series_identifier];

        if ((is_countable($group_ids) ? count($group_ids) : 0) === 0) {
            return [];
        }

        $my_groups = PermissionGroupParticipant::where(['user_id' => $xoctUser->getIliasUserId(),])
                                               ->where(
                                                   ['group_id' => $group_ids]
                                               )
                                               ->getArray(null, 'group_id');
        if (count($my_groups) === 0) {
            return [];
        }

        return PermissionGroupParticipant::where(['group_id' => $my_groups])->get();
    }

    protected static function loadGroupIdsForSeriesId(string $series_identifier): void
    {
        global $DIC;
        if (!isset(self::$series_id_to_groups_map[$series_identifier])) {
            $objectSettings = ObjectSettings::where([
                'series_identifier' => $series_identifier,
                'obj_id' => ilObject2::_lookupObjectId(
                    (int) ($DIC->http()->request()->getQueryParams()['ref_id'] ?? 0)
                ),
            ])->last();
            if (!$objectSettings instanceof ObjectSettings) {
                return;
            }
            $array = self::where(['serie_id' => $objectSettings->getObjId(),])->getArray(null, 'id');

            self::$series_id_to_groups_map[$series_identifier] = $array;
        }
    }

    /**
     * @var int
     * @con_has_field  true
     * @con_length     8
     * @con_fieldtype  integer
     */
    protected $serie_id;
    /**
     * @var string
     * @con_has_field  true
     * @con_length     1024
     * @con_fieldtype  text
     */
    protected $title;
    /**
     * @var string
     * @con_has_field  true
     * @con_length     4000
     * @con_fieldtype  text
     */
    protected $description;
    /**
     * @var int
     * @con_has_field  true
     * @con_length     1
     * @con_fieldtype  integer
     */
    protected $status;
    /**
     * @var int
     */
    protected $user_count = 0;

    public function delete(): void
    {
        /**
         * @var $gp PermissionGroupParticipant
         */
        foreach (PermissionGroupParticipant::where(['group_id' => $this->getId()])->get() as $gp) {
            $gp->delete();
        }
        parent::delete();
    }

    public function wakeUp($field_name, $field_value)
    {
        switch ($field_name) {
            case 'id':
            case 'serie_id':
            case 'status':
                return (int) $field_value;
            case 'title':
                return (string) $field_value;
            case 'description':
                return (string) $field_value;
            default:
                return null;
        }
        return null;
    }



    public function getId(): int
    {
        return (int) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getSerieId(): int
    {
        return (int) $this->serie_id;
    }

    public function setSerieId(int $serie_id): void
    {
        $this->serie_id = $serie_id;
    }

    public function getNamePresentation(): string
    {
        return (string) $this->getTitle();
    }

    public function getTitle(): string
    {
        return (string) $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return (string) $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getStatus(): int
    {
        return (int) $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }


}
