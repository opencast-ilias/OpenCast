<?php

namespace srag\Plugins\Opencast\Model\Workflow;

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
        return (WorkflowAR::count() > 0);
    }

    /**
     * @return WorkflowAR[]
     */
    public function getAllWorkflows() : array
    {
        return WorkflowAR::get();
    }


    /**
     * @param null $key
     * @param null $values
     *
     * @return array
     */
    public function getAllWorkflowsAsArray($key = null, $values = null) : array
    {
        return WorkflowAR::getArray($key, $values);
    }

    /**
     * @param string $workflow_id
     * @param string $title
     * @param string $parameters
     * @param int    $id
     */
    public function store(string $workflow_id, string $title, string $parameters, int $id = 0)
    {
        /** @var WorkflowAR $workflow */
        $workflow = new WorkflowAR($id == 0 ? null : $id);
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
        return WorkflowAR::where(['workflow_id' => $workflow_id])->hasSets();
    }


    /**
     * @param $id
     */
    public function delete($id)
    {
        $workflow = WorkflowAR::find($id);
        if (!is_null($workflow)) {
            $workflow->delete();
        }
    }

    /**
     * @param string $workflow_id
     * @return WorkflowAR|null
     */
    public function getByWorkflowId(string $workflow_id)
    {
        return WorkflowAR::where(['workflow_id' => $workflow_id])->first();
    }

    /**
     * @param int $id
     * @return WorkflowAR|null
     */
    public function getById(int $id)
    {
        return WorkflowAR::where(['id' => $id])->first();
    }
}