<?php

namespace srag\Plugins\Opencast\Model\WorkflowInstance;

use srag\Plugins\Opencast\Model\API\APIObject;
use stdClass;
use xoctException;
use xoctRequest;

/**
 * Class xoctWorkflowCollection
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class WorkflowInstanceCollection extends APIObject
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
     * @var WorkflowInstance[]
     */
    protected $workflows;


    /**
     * xoctWorkflow constructor.
     *
     * @param string $event_id
     *
     * @throws xoctException
     */
    public function __construct(string $event_id = '')
    {
        $this->event_id = $event_id;
        if ($event_id !== '') {
            $this->read();
        }
    }


    /**
     * @param stdClass $data
     *
     * @throws xoctException
     */
    public function read(stdClass $data = null)
    {
        if ($data === null) {
            $data = json_decode(xoctRequest::root()->workflows()
                ->parameter('filter', 'event_identifier:'.$this->getEventId())
                ->get()) ?: new stdClass();
        }
        $this->loadFromStdClass($data);
    }


    /**
     * @return string
     */
    public function getEventId(): string
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
     * @return WorkflowInstance[]
     */
    public function getWorkflows(): array
    {
        return $this->workflows;
    }


    /**
     * @param WorkflowInstance[] $workflows
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
    public function hasChanged(): bool
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
