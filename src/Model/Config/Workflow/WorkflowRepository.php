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
     * @param string $parameters
     * @param int    $id
     */
    public function store(string $workflow_id, string $title, string $parameters, int $id = 0)
    {
        /** @var Workflow $workflow */
        $workflow = new Workflow($id == 0 ? null : $id);
        $workflow->setWorkflowId($workflow_id);
        $workflow->setTitle($title);
        $workflow->setParameters($parameters);
        $workflow->store();
    }


    /**
     * @param string $workflow_id
     *
     * @return bool
     */
    public function exists(string $workflow_id) : bool
    {
        return Workflow::where(['workflow_id' => $workflow_id])->hasSets();
    }


    /**
     * @param $id
     */
    public function delete($id)
    {
        $workflow = Workflow::find($id);
        if (!is_null($workflow)) {
            $workflow->delete();
        }
    }

    /**
     * @param string $workflow_id
     * @return Workflow|null
     */
    public function getByWorkflowId(string $workflow_id)
    {
        return Workflow::where(['workflow_id' => $workflow_id])->first();
    }
}