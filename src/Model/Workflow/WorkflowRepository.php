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
    public function anyWorkflowExists(): bool;

    /**
     * @return WorkflowAR[]
     */
    public function getAllWorkflows(): array;

    /**
     * @param null $key
     * @param null $values
     */
    public function getAllWorkflowsAsArray($key = null, $values = null): array;

    public function store(string $workflow_id, string $title, string $description,
        string $tags, string $config_panel, int $id = 0);

    public function exists(string $workflow_id): bool;

    /**
     * @param $id
     */
    public function delete($id);

    /**
     * @return WorkflowAR|null
     */
    public function getByWorkflowId(string $workflow_id);

    /**
     * @return WorkflowAR|null
     */
    public function getById(int $id);

    /**
     * @return array
     * @throws xoctException
     */
    public function getWorkflowsFromOpencastApi(array $filter = [], bool $with_configuration_panel = false,
        bool $with_tags = false): array;

    /**
     * @return WorkflowAR
     */
    public function createOrUpdate(string $workflow_id, string $title, string $description,
        string $tags = '', string $config_panel = ''): WorkflowAR;

    /**
     * @return array
     * @throws xoctException
     */
    public function parseConfigPanels(): array;

    /**
     * @return string
     * @throws xoctException
     */
    public function buildWorkflowSelectOptions(): string;

    /**
     * @return array
     */
    public function getFilteredWorkflowsArray(array $workflows = [], ?string $tags_str = null): array;

    /**
     * @return bool
     */
    public function resetList(): bool;

    /**
     * @return bool
     */
    public function updateList(?string $tags_str = null): bool;

    /**
     * @return array
     */
    public function getConfigPanelAsArrayById(string $id): array;
}
