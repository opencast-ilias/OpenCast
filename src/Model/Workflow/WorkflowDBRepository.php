<?php

namespace srag\Plugins\Opencast\Model\Workflow;

class WorkflowDBRepository implements WorkflowRepository
{

    public function anyWorkflowExists() : bool
    {
        return (WorkflowAR::count() > 0);
    }

    public function getAllWorkflows() : array
    {
        return WorkflowAR::get();
    }


    public function getAllWorkflowsAsArray($key = null, $values = null) : array
    {
        return WorkflowAR::getArray($key, $values);
    }

    public function store(string $workflow_id, string $title, string $parameters, int $id = 0)
    {
        /** @var WorkflowAR $workflow */
        $workflow = new WorkflowAR($id == 0 ? null : $id);
        $workflow->setWorkflowId($workflow_id);
        $workflow->setTitle($title);
        $workflow->setParameters($parameters);
        $workflow->store();
    }


    public function exists(string $workflow_id) : bool
    {
        return WorkflowAR::where(['workflow_id' => $workflow_id])->hasSets();
    }


    public function delete($id)
    {
        $workflow = WorkflowAR::find($id);
        if (!is_null($workflow)) {
            $workflow->delete();
        }
    }

    public function getByWorkflowId(string $workflow_id)
    {
        return WorkflowAR::where(['workflow_id' => $workflow_id])->first();
    }

    public function getById(int $id)
    {
        return WorkflowAR::where(['id' => $id])->first();
    }
}