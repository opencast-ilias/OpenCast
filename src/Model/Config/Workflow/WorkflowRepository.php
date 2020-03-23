<?php

namespace srag\Plugins\Opencast\Model\Config\Workflow;

/**
 * Class WorkflowRepository
 *
 * @package srag\Plugins\Opencast\Model\Config\Workflow
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class WorkflowRepository
{

    /**
     * @return Workflow[]
     */
    public function getAllWorkflows() : array
    {
        return Workflow::get();
    }


    /**
     * @return array
     */
    public function getAllWorkflowsAsArray() : array
    {
        return Workflow::getArray();
    }


    /**
     * @param string $id
     * @param string $title
     * @param string $old_id
     */
    public function store(string $id, string $title, string $old_id = '')
    {
        /** @var Workflow $workflow */
        $workflow = Workflow::findOrGetInstance($old_id === '' ? $id : $old_id);
        $workflow->setId($id);
        $workflow->setTitle($title);
        $workflow->store();
    }
}