<?php

namespace srag\Plugins\Opencast\Model\WorkflowParameter\Series;

use ActiveRecord;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;

/**
 * Class xoctSeriesWorkflowParameter
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class SeriesWorkflowParameter extends ActiveRecord
{
    public const TABLE_NAME = 'xoct_series_param';

    public const VALUE_IGNORE = WorkflowParameter::VALUE_IGNORE;
    public const VALUE_ALWAYS_ACTIVE = WorkflowParameter::VALUE_ALWAYS_ACTIVE;
    public const VALUE_ALWAYS_INACTIVE = WorkflowParameter::VALUE_ALWAYS_INACTIVE;
    public const VALUE_SHOW_IN_FORM = WorkflowParameter::VALUE_SHOW_IN_FORM;
    public const VALUE_SHOW_IN_FORM_PRESET = WorkflowParameter::VALUE_SHOW_IN_FORM_PRESET;

    /**
     * @return string
     */
    public function getConnectorContainerName()
    {
        return self::TABLE_NAME;
    }


    /**
     * @var integer
     *
     * @con_sequence        true
     * @db_has_field        true
     * @db_is_unique        true
     * @db_is_primary       true
     * @db_is_notnull       true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected $id;
    /**
     * @var int
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     * @db_is_notnull       true
     */
    protected $obj_id;
    /**
     * @var string
     *
     * @db_has_field        true
     * @db_is_notnull       true
     * @db_fieldtype        text
     * @db_length           250
     */
    protected $param_id;
    /**
     * @var int
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     * @db_is_notnull       true
     */
    protected $value_member;
    /**
     * @var int
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     * @db_is_notnull       true
     */
    protected $value_admin;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @param int $id
     *
     * @return static
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }


    /**
     * @return int
     */
    public function getObjId()
    {
        return $this->obj_id;
    }


    /**
     * @param int $obj_id
     *
     * @return static
     */
    public function setObjId($obj_id)
    {
        $this->obj_id = $obj_id;

        return $this;
    }


    /**
     * @return string
     */
    public function getParamId()
    {
        return $this->param_id;
    }


    /**
     * @param string $param_id
     *
     * @return static
     */
    public function setParamId($param_id)
    {
        $this->param_id = $param_id;

        return $this;
    }


    /**
     * @return int
     */
    public function getDefaultValueMember()
    {
        return (int) $this->value_member;
    }


    /**
     * @param int $value_member
     *
     * @return static
     */
    public function setValueMember($value_member)
    {
        $this->value_member = $value_member;

        return $this;
    }


    /**
     * @return int
     */
    public function getDefaultValueAdmin()
    {
        return (int) $this->value_admin;
    }


    /**
     * @param int $value_admin
     *
     * @return static
     */
    public function setDefaultValueAdmin($value_admin)
    {
        $this->value_admin = $value_admin;

        return $this;
    }
}
