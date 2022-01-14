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
    protected $title_de;
    /**
     * @var string
     *
     * @con_has_field    true
     * @con_fieldtype    text
     * @con_length       256
     * @con_is_notnull   true
     */
    protected $title_en;
    /**
     * @var string
     *
     * @con_has_field    true
     * @con_fieldtype    text
     * @con_length       512
     * @con_is_notnull   true
     */
    protected $visible_for_permissions;
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
    /**
     * @var int
     *
     * @con_has_field    true
     * @con_fieldtype    integer
     * @con_length       8
     * @con_is_notnull   true
     */
    protected $sort;

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

    public function getTitle(string $lang_key): string
    {
        switch ($lang_key) {
            case 'de':
                return $this->title_de;
            case 'en':
            default:
                return $this->title_en;
        }
    }

    /**
     * @param string $title_de
     */
    public function setTitleDe(string $title_de): void
    {
        $this->title_de = $title_de;
    }

    /**
     * @param string $title_en
     */
    public function setTitleEn(string $title_en): void
    {
        $this->title_en = $title_en;
    }

    public function getVisibleForPermissions(): string
    {
        return $this->visible_for_permissions;
    }

    public function setVisibleForPermissions(string $visible_for_permissions)
    {
        $this->visible_for_permissions = $visible_for_permissions;
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

    /**
     * @return int
     */
    public function getSort(): int
    {
        return $this->sort;
    }

    /**
     * @param int $sort
     */
    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }
}