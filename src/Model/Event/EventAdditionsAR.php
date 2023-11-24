<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Event;

use ActiveRecord;

/**
 * Metadata of an Event that is stored only in ILIAS
 */
class EventAdditionsAR extends ActiveRecord
{
    public const TABLE_NAME = 'xoct_event_additions';

    /**
     * @deprecated
     */
    public static function returnDbTableName(): string
    {
        return self::TABLE_NAME;
    }

    public function getConnectorContainerName(): string
    {
        return self::TABLE_NAME;
    }

    public function update()
    {
        if ($this->getId() === '' || $this->getId() === '0') {
            return false;
        }
        if (!self::where(['id' => $this->getId()])->hasSets()) {
            $this->create();
        } else {
            parent::update();
        }
    }

    public function create(): void
    {
        if ($this->getId() === '' || $this->getId() === '0') {
            return;
        }
        parent::create();
    }

    /**
     * @var string
     *
     * @description    Unique identifier from opencast
     *
     * @con_is_primary true
     * @con_is_unique  true
     * @con_has_field  true
     * @con_fieldtype  text
     * @con_length     64
     */
    protected $id;
    /**
     * @var bool
     *
     * @con_has_field  true
     * @con_fieldtype  integer
     * @con_length     1
     */
    protected $is_online = true;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getIsOnline(): bool
    {
        return $this->is_online;
    }

    public function setIsOnline(bool $is_online): void
    {
        $this->is_online = $is_online;
    }
}
