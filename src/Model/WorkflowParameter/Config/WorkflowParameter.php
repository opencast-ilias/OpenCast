<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\WorkflowParameter\Config;

use ActiveRecord;

/**
 * Class xoctWorkflowParameter
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class WorkflowParameter extends ActiveRecord
{
    public const TABLE_NAME = 'xoct_workflow_param';

    public const VALUE_IGNORE = 0;
    public const VALUE_ALWAYS_ACTIVE = 1;
    public const VALUE_ALWAYS_INACTIVE = 2;
    public const VALUE_SHOW_IN_FORM = 3;
    public const VALUE_SHOW_IN_FORM_PRESET = 4;

    public const TYPE_CHECKBOX = 'checkbox';

    public static $possible_values = [
        self::VALUE_IGNORE,
        self::VALUE_ALWAYS_ACTIVE,
        self::VALUE_ALWAYS_INACTIVE,
        self::VALUE_SHOW_IN_FORM,
        self::VALUE_SHOW_IN_FORM_PRESET
    ];

    public function getConnectorContainerName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * @var string
     *
     * @db_has_field        true
     * @db_is_unique        true
     * @db_is_primary       true
     * @db_is_notnull       true
     * @db_fieldtype        text
     * @db_length           250
     */
    protected $id;
    /**
     * @var string
     *
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_is_notnull       true
     * @db_length           256
     */
    protected $title;
    /**
     * @var string
     *
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           256
     */
    protected $type;
    /**
     * @var int
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected $default_value_member = self::VALUE_IGNORE;
    /**
     * @var int
     *
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected $default_value_admin = self::VALUE_IGNORE;

    public function getId(): string
    {
        return $this->id ?? '';
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getType(): string
    {
        return $this->type ?? '';
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getDefaultValueMember(): int
    {
        return (int) $this->default_value_member;
    }

    /**
     * @param integer $default_value_member
     *
     * @return WorkflowParameter
     */
    public function setDefaultValueMember($default_value_member): self
    {
        $this->default_value_member = $default_value_member;
        return $this;
    }

    public function getDefaultValueAdmin(): int
    {
        return (int) $this->default_value_admin;
    }

    /**
     * @param int $default_value_admin
     *
     * @return WorkflowParameter
     */
    public function setDefaultValueAdmin($default_value_admin): self
    {
        $this->default_value_admin = $default_value_admin;
        return $this;
    }
}
