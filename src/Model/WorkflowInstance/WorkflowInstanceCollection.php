<?php

namespace srag\Plugins\Opencast\Model\WorkflowInstance;

use srag\Plugins\Opencast\Model\API\APIObject;
use stdClass;
use xoctException;
use srag\Plugins\Opencast\API\OpencastAPI;
use srag\Plugins\Opencast\API\API;

/**
 * Class xoctWorkflowCollection
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class WorkflowInstanceCollection extends APIObject
{
    /**
     * @var API
     */
    protected $api;
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
     *
     * @throws xoctException
     */
    public function __construct(string $event_id = '')
    {
        global $opencastContainer;
        $this->api = $opencastContainer[API::class];
        $this->event_id = $event_id;
        if ($event_id !== '') {
            $this->read();
        }
    }

    /**
     * @throws xoctException
     * @deprecated since OpencastAPI v1.3 because this endpoint is removed from Opencast Verison 12.x, we should no longer support it here.
     */
    public function read(stdClass $data = null): void
    {
        if ($data === null) {
            $data = new stdClass();
            $opencast_api = $this->api->routes();
            $opencast_version = $opencast_api->sysinfo->getVersion()->version;
            // A deep check to avoid error in advance.
            // workflows get all endpoint is removed from Opencast Verison 12.x and in OpencastAPI v1.3 is flagged depricated.
            if (version_compare($opencastversion, '12.0.0', '<') && method_exists($opencast_api->workflowsApi, 'getAll')) {
                $workflow_instance = $opencast_api->workflowsApi->getAll([
                    'filter' => [
                        'event_identifier' => $this->getEventId()
                    ]
                ]);
                if (!empty($workflow_instance)) {
                    $data = $workflow_instance;
                }
            }
        }
        $this->loadFromStdClass($data);
    }

    public function getEventId(): string
    {
        return $this->event_id;
    }

    public function setEventId(string $event_id): void
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
    public function setWorkflows(array $workflows): void
    {
        if ($this->workflows !== $workflows) {
            $this->has_changed = true;
        }
        $this->workflows = $workflows;
    }

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

    public function setHasChanged(bool $has_changed): void
    {
        $this->has_changed = $has_changed;
    }
}
