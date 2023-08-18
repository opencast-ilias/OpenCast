<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config;

use ActiveRecord;
use xoctException;

abstract class MDFieldConfigAR extends ActiveRecord
{
    public const VISIBLE_ALL = 'all';
    public const VISIBLE_ADMIN = 'admin';
    public const VALUE_SEPERATOR = "|||";
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

    /**
     * @var array
     *
     * @con_has_field    true
     * @con_fieldtype    text
     * @con_is_notnull   false
     */
    protected $values = [];

    public function sleep($field_name)
    {
        switch ($field_name) {
            case 'prefill':
                return $this->prefill->getValue();
            case 'values':
                return json_encode($this->values, JSON_THROW_ON_ERROR);
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
            case 'values':
                if (empty($field_value)) {
                    return [];
                }
                $decoded = json_decode($field_value, true, 512, JSON_THROW_ON_ERROR);
                return is_array($decoded) ? $decoded : [];
            default:
                return null;
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getFieldId(): string
    {
        return $this->field_id;
    }

    public function setFieldId(string $field_id): void
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

    public function setTitleDe(string $title_de): void
    {
        $this->title_de = $title_de;
    }

    public function setTitleEn(string $title_en): void
    {
        $this->title_en = $title_en;
    }

    public function getVisibleForPermissions(): string
    {
        return $this->visible_for_permissions;
    }

    public function setVisibleForPermissions(string $visible_for_permissions): void
    {
        $this->visible_for_permissions = $visible_for_permissions;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): void
    {
        $this->required = $required;
    }

    public function isReadOnly(): bool
    {
        return $this->read_only;
    }

    public function setReadOnly(bool $read_only): void
    {
        $this->read_only = $read_only;
    }

    public function getPrefill(): MDPrefillOption
    {
        return $this->prefill;
    }

    public function setPrefill(MDPrefillOption $prefill): void
    {
        $this->prefill = $prefill;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function setSort(int $sort): void
    {
        $this->sort = $sort;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setValues(array $values): void
    {
        $this->values = $values;
    }

    public function getValuesAsEditableString(): string
    {
        $string = '';
        foreach ($this->values as $key => $value) {
            $string .= $key . self::VALUE_SEPERATOR . $value . "\n";
        }

        return $string;
    }

    public function setValuesFromEditableString(string $values): void
    {
        $this->values = [];
        if (empty($values)) {
            return;
        }
        // normalize line endings
        $values = str_replace("\r\n", "\n", $values);
        foreach (explode("\n", $values) as $value) {
            $value = explode(self::VALUE_SEPERATOR, $value);
            if (count($value) === 2) {
                $this->values[$value[0]] = $value[1];
            }
        }
    }
}
