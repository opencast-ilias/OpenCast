<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\WorkflowParameter;

use JsonSerializable;
use stdClass;

class Processing implements JsonSerializable
{
    public function __construct(
        protected string $workflow,
        /**
         * key value pair for workflow configurations
         */
        protected \stdClass $configuration
    ) {
    }

    public function getWorkflow(): string
    {
        return $this->workflow;
    }

    public function getConfiguration(): stdClass
    {
        return $this->configuration;
    }

    public function jsonSerialize(): mixed
    {
        return (object) [
            'workflow' => $this->getWorkflow(),
            'configuration' => $this->getConfiguration()
        ];
    }
}
