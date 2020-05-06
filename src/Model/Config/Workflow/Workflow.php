<?php

namespace srag\Plugins\Opencast\Model\Config\Workflow;

use ActiveRecord;

/**
 * Class Workflow
 *
 * @package srag\Plugins\Opencast\Model\Config\Workflow
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class Workflow extends ActiveRecord
{

    const TABLE_NAME = 'xoct_workflow';


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
     * @con_has_field    true
     * @con_fieldtype    integer
     * @con_length       8
     * @con_is_primary   true
     * @con_sequence     true
     */
    protected $id;
    /**
     * @var string
     *
     * @con_is_unique  true
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     64
     */
    protected $workflow_id;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     512
     */
    protected $title;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     512
     */
    protected $parameters;


    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }


    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;
    }


    /**
     * @return string
     */
    public function getWorkflowId() : string
    {
        return $this->workflow_id;
    }


    /**
     * @param string $workflow_id
     */
    public function setWorkflowId(string $workflow_id)
    {
        $this->workflow_id = $workflow_id;
    }


    /**
     * @return string
     */
    public function getTitle() : string
    {
        return $this->title;
    }


    /**
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getParameters() : string
    {
        return $this->parameters ?: '';
    }

    /**
     * @param string $parameters
     */
    public function setParameters(string $parameters)
    {
        $this->parameters = $parameters;
    }


}