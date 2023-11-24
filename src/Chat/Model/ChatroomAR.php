<?php

/** @noinspection PhpIncompatibleReturnTypeInspection */

declare(strict_types=1);

namespace srag\Plugins\Opencast\Chat\Model;

use ActiveRecord;

/**
 * Class ChatroomAR
 *
 * @package srag\Plugins\Opencast\Chat
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ChatroomAR extends ActiveRecord
{
    public const TABLE_NAME = 'sr_chat_room';

    public static function findOrCreate(string $event_id, int $obj_id): ChatroomAR
    {
        $chatroom = self::where(['event_id' => $event_id, 'obj_id' => $obj_id])->first();
        if (!$chatroom) {
            $chatroom = new self();
            $chatroom->setEventId($event_id);
            $chatroom->setObjId($obj_id);
            $chatroom->create();
        }
        return $chatroom;
    }

    /** @noinspection PhpIncompatibleReturnTypeInspection */
    public static function findBy(string $event_id, int $obj_id): ChatroomAR
    {
        return self::where(['event_id' => $event_id, 'obj_id' => $obj_id])->first();
    }

    public static function chatroomExists(string $event_id, int $obj_id): bool
    {
        return self::where(['event_id' => $event_id, 'obj_id' => $obj_id])->hasSets();
    }

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
     * @db_is_notnull       true
     * @db_has_field        true
     * @db_fieldtype        text
     * @db_length           56
     */
    protected $event_id;

    /**
     * @var int
     *
     * @db_has_field        true
     * @db_is_notnull       true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected $obj_id;

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getEventId(): string
    {
        return (string) $this->event_id;
    }

    public function setEventId(string $event_id): void
    {
        $this->event_id = $event_id;
    }

    public function getObjId(): int
    {
        return (int) $this->obj_id;
    }

    public function setObjId(int $obj_id): void
    {
        $this->obj_id = $obj_id;
    }
}
