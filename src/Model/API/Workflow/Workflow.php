<?php

namespace srag\Plugins\Opencast\Model\API\Workflow;

/**
 * Class xoctWorkflow
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class Workflow
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
     * @var
     */
    protected $configuration;


    /**
     * xoctWorkflow constructor.
     */
    public function __construct()
    {

    }


    /**
     * @return bool
     */
    public function hasChanged() : bool
    {
        return $this->has_changed;
    }


    /**
     * @param bool $has_changed
     */
    public function setHasChanged(bool $has_changed)
    {
        $this->has_changed = $has_changed;
    }


    /**
     * @return string
     */
    public function getWorkflowDefinitionIdentifier() : string
    {
        return $this->workflow_definition_identifier;
    }


    /**
     * @param string $workflow_definition_identifier
     */
    public function setWorkflowDefinitionIdentifier(string $workflow_definition_identifier)
    {
        $this->workflow_definition_identifier = $workflow_definition_identifier;
    }


    /**
     * @return int
     */
    public function getIdentifier() : int
    {
        return $this->identifier;
    }


    /**
     * @param int $identifier
     */
    public function setIdentifier(int $identifier)
    {
        $this->identifier = $identifier;
    }


    /**
     * @return string
     */
    public function getCreator() : string
    {
        return $this->creator;
    }


    /**
     * @param string $creator
     */
    public function setCreator(string $creator)
    {
        $this->creator = $creator;
    }


    /**
     * @return WorkflowOperation[]
     */
    public function getOperations() : array
    {
        return $this->operations;
    }


    /**
     * @param WorkflowOperation[] $operations
     */
    public function setOperations(array $operations)
    {
        $this->operations = $operations;
    }
}