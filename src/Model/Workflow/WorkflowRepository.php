<?php

namespace srag\Plugins\Opencast\Model\Workflow;

/**
 * Class WorkflowRepository
 *
 * @package srag\Plugins\Opencast\Model\Config\Workflow
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
interface WorkflowRepository
{
    /**
     * @return bool
     */
    public function anyWorkflowExists(): bool;

    /**
     * @return WorkflowAR[]
     */
    public function getAllWorkflows(): array;

    /**
     * @param null $key
     * @param null $values
     *
     * @return array
     */
    public function getAllWorkflowsAsArray($key = null, $values = null): array;

    /**
     * @param string $workflow_id
     * @param string $title
     * @param string $parameters
     * @param int $id
     */
    public function store(string $workflow_id, string $title, string $parameters, int $id = 0);

    /**
     * @param string $workflow_id
     *
     * @return bool
     */
    public function exists(string $workflow_id): bool;

    /**
     * @param $id
     */
    public function delete($id);

    /**
     * @param string $workflow_id
     * @return WorkflowAR|null
     */
    public function getByWorkflowId(string $workflow_id);

    /**
     * @param int $id
     * @return WorkflowAR|null
     */
    public function getById(int $id);
}
