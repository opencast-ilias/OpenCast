<?php

namespace srag\Plugins\Opencast\Model\API\Workflow;

/**
 * Class xoctWorkflowCollection
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class WorkflowCollection
{

    /**
     * @var bool
     */
    protected $has_changed = false;
    /**
     * @var string
     */
    protected $event_id;
    /**
     * @var Workflow[]
     */
    protected $workflows;


    /**
     * xoctWorkflow constructor.
     *
     * @param string $event_id
     */
    public function __construct(string $event_id = '')
    {
        $this->event_id = $event_id;
        if ($event_id !== '') {
            $this->read();
        }
    }


    /**
     *
     */
    public function read()
    {

    }


    /**
     * @return string
     */
    public function getEventId() : string
    {
        return $this->event_id;
    }


    /**
     * @param string $event_id
     */
    public function setEventId(string $event_id)
    {
        $this->event_id = $event_id;
    }


    /**
     * @return Workflow[]
     */
    public function getWorkflows() : array
    {
        return $this->workflows;
    }


    /**
     * @param Workflow[] $workflows
     */
    public function setWorkflows(array $workflows)
    {
        if ($this->workflows != $workflows) {
            $this->has_changed = true;
        }
        $this->workflows = $workflows;
    }


    /**
     * @return bool
     */
    public function hasChanged() : bool
    {
        if ($this->has_changed) {
            return true;
        }
        foreach ($this->workflows as $workflow) {
            if ($workflow->hasChanged()) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param bool $has_changed
     */
    public function setHasChanged(bool $has_changed)
    {
        $this->has_changed = $has_changed;
    }
}