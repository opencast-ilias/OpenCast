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
     * @return bool
     */
    public function anyWorkflowExists() : bool
    {
        return (Workflow::count() > 0);
    }

    /**
     * @return Workflow[]
     */
    public function getAllWorkflows() : array
    {
        return Workflow::get();
    }


    /**
     * @param null $key
     * @param null $values
     *
     * @return array
     */
    public function getAllWorkflowsAsArray($key = null, $values = null) : array
    {
        return Workflow::getArray($key, $values);
    }


    /**
     * @param string $workflow_id
     * @param string $title
     * @param int    $id
     */
    public function store(string $workflow_id, string $title, int $id = 0)
    {
        /** @var Workflow $workflow */
        $workflow = new Workflow($id == 0 ? null : $id);
        $workflow->setWorkflowId($workflow_id);
        $workflow->setTitle($title);
        $workflow->store();
    }
}