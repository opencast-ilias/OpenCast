<?php

namespace srag\Plugins\Opencast\TermsOfUse;

use ActiveRecord;

/**
 * class AcceptedToU
 * Holds the information which user has accepted the terms of use for which OpenCast instance
 *
 * @author fluxlabs <connect@fluxlabs.ch>
 * @author Sophie Pfister <sophie@fluxlabs.ch>
 *
 */
class AcceptedToU extends ActiveRecord
{
    /**
     * PrimaryKey ID
     * @var int
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_is_primary true
     * @con_sequence   true
     */
    protected $id;

    /**
     * @var int
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_is_notnull true
     */
    protected $user_id;

    /**
     * @var int
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_is_notnull true
     */
    protected $oc_instance_id;

    /**
     * Has the user accepted the terms of use for this OpenCast-Instance?
     * @var bool
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     1
     * @con_is_notnull true
     */
    protected $tou_accepted = false;

    public function getId() : int
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;
    }

    public function getUserId() : int
    {
        return $this->user_id;
    }

    public function setUserId(int $id)
    {
        $this->user_id = $id;
    }

    public function getOCInstanceId() : int
    {
        return $this->oc_instance_id;
    }

    public function setOCInstanceId(int $id)
    {
        $this->oc_instance_id = $id;
    }

    public function hasAccepted() : bool
    {
        return $this->tou_accepted;
    }

    public function setAccepted()
    {
        $this->tou_accepted = true;
    }

    public function resetAccepted()
    {
        $this->tou_accepted = false;
    }

    public function sleep($field_name)
    {

        switch ($field_name) {
            case "tou_accepted":
                $value = $this->{$field_name};
                return $value ? 1 : 0;
            default:
                return parent::sleep($field_name);
        }
    }

    public function wakeUp($field_name, $field_value)
    {
        switch ($field_name) {
            case "tou_accepted":
                return boolval($field_value);
            default:
                return parent::wakeUp($field_name, $field_value);
        }
    }
}