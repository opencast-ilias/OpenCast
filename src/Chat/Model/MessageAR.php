<?php

namespace srag\Plugins\Opencast\Chat\Model;

use ActiveRecord;

/**
 * Class MessageAR
 *
 * @package srag\Plugins\Opencast\Chat
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
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

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getChatRoomId()
    {
        return $this->chat_room_id;
    }

    /**
     * @param int $chat_room_id
     */
    public function setChatRoomId($chat_room_id): void
    {
        $this->chat_room_id = $chat_room_id;
    }

    /**
     * @return int
     */
    public function getUsrId()
    {
        return $this->usr_id;
    }

    /**
     * @param int $usr_id
     */
    public function setUsrId($usr_id): void
    {
        $this->usr_id = $usr_id;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message): void
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getSentAt()
    {
        return $this->sent_at;
    }

    /**
     * @param string $sent_at
     */
    public function setSentAt($sent_at): void
    {
        $this->sent_at = $sent_at;
    }
}
