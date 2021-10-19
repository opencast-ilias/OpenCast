<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config;

use ActiveRecord;
use xoctException;

class MDFieldConfigAR extends ActiveRecord
{
    const TABLE_NAME = 'xoct_md_field';

    /**
     * @var int
     *
     * @con_has_field    true
     * @con_fieldtype    integer
     * @con_length       8
     * @con_is_primary   true
     * @con_sequence     true
     */
    private $id;
    /**
     * @var string
     *
     * @con_has_field    true
     * @con_fieldtype    text
     * @con_length       128
     * @con_is_notnull   true
     */
    private $field_id;
    /**
     * @var string
     *
     * @con_has_field    true
     * @con_fieldtype    text
     * @con_length       256
     * @con_is_notnull   true
     */
    private $title;
    /**
     * @var int[]
     *
     * @con_has_field    true
     * @con_fieldtype    text
     * @con_length       512
     * @con_is_notnull   true
     */
    private $visible_for_roles;
    /**
     * @var bool
     *
     * @con_has_field    true
     * @con_fieldtype    int
     * @con_length       1
     * @con_is_notnull   true
     */
    private $required;
    /**
     * @var bool
     *
     * @con_has_field    true
     * @con_fieldtype    int
     * @con_length       1
     * @con_is_notnull   true
     */
    private $read_only;
    /**
     * @var MDPrefillOption
     *
     * @con_has_field    true
     * @con_fieldtype    text
     * @con_length       128
     * @con_is_notnull   true
     */
    private $prefill;

    public function sleep($field_name)
    {
        switch ($field_name) {
            case 'prefill':
                return $this->prefill->getValue();
            default:
                return null;
        }
    }

    /**
     * @throws xoctException
     */
    public function wakeUp($field_name, $field_value)
    {
        switch ($field_name) {
            case 'prefill':
                return new MDPrefillOption($field_value);
            default:
                return null;
        }
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getFieldId(): string
    {
        return $this->field_id;
    }

    /**
     * @param string $field_id
     */
    public function setFieldId(string $field_id)
    {
        $this->field_id = $field_id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return int[]
     */
    public function getVisibleForRoles(): array
    {
        return $this->visible_for_roles;
    }

    /**
     * @param int[] $visible_for_roles
     */
    public function setVisibleForRoles(array $visible_for_roles)
    {
        $this->visible_for_roles = $visible_for_roles;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @param bool $required
     */
    public function setRequired(bool $required)
    {
        $this->required = $required;
    }

    /**
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return $this->read_only;
    }

    /**
     * @param bool $read_only
     */
    public function setReadOnly(bool $read_only)
    {
        $this->read_only = $read_only;
    }

    /**
     * @return MDPrefillOption
     */
    public function getPrefill(): MDPrefillOption
    {
        return $this->prefill;
    }

    /**
     * @param MDPrefillOption $prefill
     */
    public function setPrefill(MDPrefillOption $prefill)
    {
        $this->prefill = $prefill;
    }


    public function getConnectorContainerName()
    {
        return self::TABLE_NAME;
    }
}