<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\UserSettings;

use ActiveRecord;

/**
 * Class xoctUserViewType
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
#[\AllowDynamicProperties]
class UserSetting extends ActiveRecord
{
    public const TABLE_NAME = 'xoct_user_setting';

    public function getConnectorContainerName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * @var integer
     *
     * @con_has_field     true
     * @con_fieldtype     integer
     * @con_length        8
     * @con_is_primary    true
     * @con_sequence      true
     * @con_is_notnull    true
     */
    protected $id;
    /**
     * @var integer
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     8
     * @con_is_notnull true
     */
    protected $ref_id;
    /**
     * @var integer
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     8
     * @con_is_notnull true
     */
    protected $user_id;
    /**
     * @var string
     *
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     56
     * @con_is_notnull true
     */
    protected $name;
    /**
     * @var integer
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     8
     * @con_is_notnull true
     */
    protected $value;

    public function getRefId(): int
    {
        return (int) $this->ref_id;
    }

    public function setRefId(int $ref_id): self
    {
        $this->ref_id = $ref_id;
        return $this;
    }

    public function getUserId(): int
    {
        return (int) $this->user_id;
    }

    public function setUserId(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    public function getValue(): int
    {
        return (int) $this->value;
    }

    public function setValue(int $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
}
