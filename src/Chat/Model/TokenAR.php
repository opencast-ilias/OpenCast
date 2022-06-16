<?php

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


    /**
     * @param $chat_room_id int
     * @param $usr_id int
     * @param $public_name string
     *
     * @return $this
     */
    public static function getNewFrom($chat_room_id, $usr_id, $public_name)
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


    /**
     * @return string
     */
    public function getConnectorContainerName()
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


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getChatRoomId()
    {
        return $this->chat_room_id;
    }


    /**
     * @return int
     */
    public function getUsrId()
    {
        return $this->usr_id;
    }


    /**
     * @return string
     */
    public function getPublicName()
    {
        return $this->public_name;
    }



    /**
     * @return Token
     */
    public function getToken()
    {
        return $this->token;
    }


    /**
     * @return int
     */
    public function getValidUntilUnix()
    {
        return $this->valid_until_unix;
    }



    /**
     * @param $field_name
     *
     * @return string|null
     */
    public function sleep($field_name)
    {
        switch ($field_name) {
            case 'token':
                return $this->token->toString();
            default:
                return null;
        }
    }


    /**
     * @param $field_name
     * @param $field_value
     *
     * @return Token|null
     */
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
