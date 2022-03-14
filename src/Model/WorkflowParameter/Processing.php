<?php

namespace srag\Plugins\Opencast\Model\WorkflowParameter;

use JsonSerializable;
use stdClass;

class Processing implements JsonSerializable
{
    /**
     * @var string
     */
    protected $workflow;
    /**
     * key value pair for workflow configurations
     * @var stdClass
     */
    protected $configuration;

    /**
     * @param string $workflow
     * @param stdClass $configuration
     */
    public function __construct(string $workflow, stdClass $configuration)
    {
        $this->workflow = $workflow;
        $this->configuration = $configuration;
    }

    /**
     * @return string
     */
    public function getWorkflow(): string
    {
        return $this->workflow;
    }

    /**
     * @return stdClass
     */
    public function getConfiguration(): stdClass
    {
        return $this->configuration;
    }

    public function jsonSerialize()
    {
        return (object) [
            'workflow' => $this->getWorkflow(),
            'configuration' => $this->getConfiguration()
        ];
    }
}