<?php

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
     * @param $id
     *
     * @return PermissionGroup[]
     */
    public static function getAllForId($id, $call_by_reference = false)
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
     * @param          $series_identifier
     * @param xoctUser $xoctUser
     *
     * @return PermissionGroupParticipant[]
     */
    public static function getAllGroupParticipantsOfUser($series_identifier, xoctUser $xoctUser)
    {
        self::loadGroupIdsForSeriesId($series_identifier);
        $group_ids = self::$series_id_to_groups_map[$series_identifier];

        if (count($group_ids) == 0) {
            return [];
        }

        $my_groups = PermissionGroupParticipant::where(['user_id' => $xoctUser->getIliasUserId(),])->where(['group_id' => $group_ids])
            ->getArray(null, 'group_id');
        if (count($my_groups) == 0) {
            return [];
        }

        return PermissionGroupParticipant::where(['group_id' => $my_groups])->get();
    }


    /**
     * @param $series_identifier
     *
     * @return array
     */
    protected static function loadGroupIdsForSeriesId($series_identifier)
    {
        if (!isset(self::$series_id_to_groups_map[$series_identifier])) {
            $objectSettings = ObjectSettings::where([
                'series_identifier' => $series_identifier,
                'obj_id' => ilObject2::_lookupObjectId($_GET['ref_id']),
            ])->last();
            if (!$objectSettings instanceof ObjectSettings) {
                return [];
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


    public function delete()
    {
        /**
         * @var $gp PermissionGroupParticipant
         */
        foreach (PermissionGroupParticipant::where(['group_id' => $this->getId()])->get() as $gp) {
            $gp->delete();
        }
        parent::delete();
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
    public function getSerieId()
    {
        return $this->serie_id;
    }


    /**
     * @param int $serie_id
     */
    public function setSerieId($serie_id)
    {
        $this->serie_id = $serie_id;
    }


    /**
     * @return string
     */
    public function getNamePresentation()
    {
        return $this->getTitle();
    }


    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }


    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }


    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }


    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
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
}
