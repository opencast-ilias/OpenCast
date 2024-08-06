<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Chat\Model;

use ActiveRecord;

/**
 * Class MessageAR
 *
 * @package srag\Plugins\Opencast\Chat
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
#[\AllowDynamicProperties]
class MessageAR extends ActiveRecord
{
    public const TABLE_NAME = 'sr_chat_message';

    public function getConnectorContainerName(): string
    {
        return self::TABLE_NAME;
    }

    /**
     * @var string
     *
     * @con_has_field    true
     * @con_fieldtype    text
     * @con_length       56
     * @con_is_primary   true
     */
    protected $id;

    /**
     * @var int
     *
     * @db_is_notnull       true
     * @db_has_field        true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected $chat_room_id;

    /**
     * @var int
     *
     * @db_has_field        true
     * @db_is_notnull       true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected $usr_id;

    /**
     * @var string
     *
     * @db_has_field        true
     * @db_is_notnull       true
     * @db_fieldtype        text
     * @db_length           512
     */
    protected $message;

    /**
     * @var string
     *
     * @db_has_field        true
     * @db_is_notnull       true
     * @db_fieldtype        timestamp
     */
    protected $sent_at;

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getChatRoomId(): int
    {
        return (int) $this->chat_room_id;
    }

    public function setChatRoomId(int $chat_room_id): void
    {
        $this->chat_room_id = $chat_room_id;
    }

    public function getUsrId(): int
    {
        return (int) $this->usr_id;
    }

    public function setUsrId(int $usr_id): void
    {
        $this->usr_id = $usr_id;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getSentAt(): string
    {
        return $this->sent_at;
    }

    public function setSentAt(string $sent_at): void
    {
        $this->sent_at = $sent_at;
    }
}
