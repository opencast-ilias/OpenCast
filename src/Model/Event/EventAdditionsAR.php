<?php

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

    public function create()
    {
        if ($this->getId() === '' || $this->getId() === '0') {
            return false;
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

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return boolean
     */
    public function getIsOnline()
    {
        return $this->is_online;
    }

    /**
     * @param boolean $is_online
     */
    public function setIsOnline($is_online): void
    {
        $this->is_online = $is_online;
    }
}
