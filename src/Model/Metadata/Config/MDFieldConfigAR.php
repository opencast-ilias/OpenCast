<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config;

use ActiveRecord;
use xoctException;

abstract class MDFieldConfigAR extends ActiveRecord
{
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
     * @con_has_field    true
     * @con_fieldtype    text
     * @con_is_unique    true
     * @con_length       128
     * @con_is_notnull   true
     */
    protected $field_id;
    /**
     * @var string
     *
     * @con_has_field    true
     * @con_fieldtype    text
     * @con_length       256
     * @con_is_notnull   true
     */
    protected $title;
    /**
     * @var string[]
     *
     * @con_has_field    true
     * @con_fieldtype    text
     * @con_length       512
     * @con_is_notnull   true
     */
    protected $visible_for_roles;
    /**
     * @var bool
     *
     * @con_has_field    true
     * @con_fieldtype    integer
     * @con_length       1
     * @con_is_notnull   true
     */
    protected $required;
    /**
     * @var bool
     *
     * @con_has_field    true
     * @con_fieldtype    integer
     * @con_length       1
     * @con_is_notnull   true
     */
    protected $read_only;
    /**
     * @var MDPrefillOption
     *
     * @con_has_field    true
     * @con_fieldtype    text
     * @con_length       128
     * @con_is_notnull   true
     */
    protected $prefill;

    public function sleep($field_name)
    {
        switch ($field_name) {
            case 'prefill':
                return $this->prefill->getValue();
            case 'visible_for_roles':
                return serialize($this->visible_for_roles);
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
            case 'visible_for_roles':
                return unserialize($field_value);
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
     * @return string[]
     */
    public function getVisibleForRoles(): array
    {
        return $this->visible_for_roles;
    }

    /**
     * @param string[] $visible_for_roles
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
}