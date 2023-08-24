<?php

namespace srag\Plugins\Opencast\Model\WorkflowInstance;

use srag\Plugins\Opencast\Model\API\APIObject;
use stdClass;

/**
 * Class xoctWorkflow
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class WorkflowInstance extends APIObject
{
    /**
     * @var bool
     */
    protected $has_changed = false;
    /**
     * @var string
     */
    protected $workflow_definition_identifier;
    /**
     * @var int
     */
    protected $identifier;
    /**
     * @var string
     */
    protected $creator;
    /**
     * @var WorkflowOperation[]
     */
    protected $operations;
    /**
     * @var stdClass
     */
    protected $configuration;

    /**
     * xoctWorkflow constructor.
     */
    public function __construct()
    {
    }

    public function hasChanged(): bool
    {
        return $this->has_changed;
    }

    public function setHasChanged(bool $has_changed): void
    {
        $this->has_changed = $has_changed;
    }

    public function getWorkflowDefinitionIdentifier(): string
    {
        return $this->workflow_definition_identifier;
    }

    public function setWorkflowDefinitionIdentifier(string $workflow_definition_identifier): void
    {
        $this->workflow_definition_identifier = $workflow_definition_identifier;
    }

    public function getIdentifier(): int
    {
        return $this->identifier;
    }

    public function setIdentifier(int $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getCreator(): string
    {
        return $this->creator;
    }

    public function setCreator(string $creator): void
    {
        $this->creator = $creator;
    }

    /**
     * @return WorkflowOperation[]
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * @param WorkflowOperation[] $operations
     */
    public function setOperations(array $operations): void
    {
        $this->operations = $operations;
    }

    public function getConfiguration(): stdClass
    {
        return $this->configuration;
    }

    public function setConfiguration(stdClass $configuration): void
    {
        if ($this->configuration !== $configuration) {
            $this->has_changed = true;
        }
        $this->configuration = $configuration;
    }
}
