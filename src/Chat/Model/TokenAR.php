<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Chat\Model;

use ActiveRecord;

/**
 * Class TokenAR
 *
 * @package srag\Plugins\Opencast\Chat
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class TokenAR extends ActiveRecord
{
    public const TABLE_NAME = 'sr_chat_token';

    /**
     * validity in seconds
     */
    public const TOKEN_VALIDITY = 60 * 60;

    public static function getNewFrom(int $chat_room_id, int $usr_id, string $public_name): self
    {
        $self = new self();
        $self->chat_room_id = $chat_room_id;
        $self->usr_id = $usr_id;
        $self->public_name = $public_name;
        $self->valid_until_unix = time() + self::TOKEN_VALIDITY;
        $self->token = new Token();
        $self->create();

        return $self;
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
     * @db_length           128
     */
    protected $public_name;

    /**
     * @var Token
     *
     * @db_has_field        true
     * @db_is_notnull       true
     * @db_fieldtype        text
     * @db_length           128
     */
    protected $token;

    /**
     * @var int
     *
     * @db_has_field        true
     * @db_is_notnull       true
     * @db_fieldtype        integer
     * @db_length           8
     */
    protected $valid_until_unix;

    public function getId(): int
    {
        return $this->id;
    }

    public function getChatRoomId(): int
    {
        return $this->chat_room_id;
    }

    public function getUsrId(): int
    {
        return $this->usr_id;
    }

    public function getPublicName(): string
    {
        return $this->public_name;
    }

    public function getToken(): Token
    {
        return $this->token;
    }

    public function getValidUntilUnix(): int
    {
        return $this->valid_until_unix;
    }

    public function sleep($field_name)
    {
        switch ($field_name) {
            case 'token':
                return $this->token->toString();
            default:
                return null;
        }
    }

    public function wakeUp($field_name, $field_value)
    {
        switch ($field_name) {
            case 'token':
                return new Token($field_value);
            default:
                return null;
        }
    }
}
