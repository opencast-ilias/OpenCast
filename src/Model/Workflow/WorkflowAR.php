<?php

namespace srag\Plugins\Opencast\Model\Workflow;

use ActiveRecord;

/**
 * Class Workflow
 *
 * @package srag\Plugins\Opencast\Model\Config\Workflow
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class WorkflowAR extends ActiveRecord
{
    public const TABLE_NAME = 'xoct_workflow';

    public function getConnectorContainerName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * @var int
     *
     * @con_has_field    true
     * @con_fieldtype    integer
     * @con_length       8
     * @con_is_primary   true
     * @con_sequence     true
     */
    protected $id;
    /**
     * @var string
     *
     * @con_is_unique  true
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     64
     */
    protected $workflow_id;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     512
     */
    protected $title;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     512
     */
    protected $description;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     512
     */
    protected $tags;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     512
     */
    protected $roles;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype clob
     */
    protected $config_panel;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getWorkflowId(): string
    {
        return $this->workflow_id;
    }

    public function setWorkflowId(string $workflow_id): void
    {
        $this->workflow_id = $workflow_id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description ?: '';
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getTags(): string
    {
        return $this->tags ?: '';
    }

    public function setTags(string $tags): void
    {
        $this->tags = $tags;
    }

    public function getRoles(): string
    {
        return $this->roles ?: '';
    }

    public function setRoles(string $roles): void
    {
        $this->roles = $roles;
    }

    public function getConfigPanel(): string
    {
        return $this->config_panel ?: '';
    }

    public function setConfigPanel(string $config_panel): void
    {
        $this->config_panel = $config_panel;
    }
}
