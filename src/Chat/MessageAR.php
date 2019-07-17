<?php

namespace srag\Plugins\Opencast\Chat;

use ActiveRecord;

/**
 * Class MessageAR
 *
 * @package srag\Plugins\Opencast\Chat
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class MessageAR extends ActiveRecord {

	const TABLE_NAME = 'sr_chat_message';


	/**
	 * @return string
	 */
	public function getConnectorContainerName() {
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
	protected $message;


	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param int $id
	 */
	public function setId($id) {
		$this->id = $id;
	}


	/**
	 * @return int
	 */
	public function getChatRoomId() {
		return $this->chat_room_id;
	}


	/**
	 * @param int $chat_room_id
	 */
	public function setChatRoomId($chat_room_id) {
		$this->chat_room_id = $chat_room_id;
	}


	/**
	 * @return int
	 */
	public function getUsrId() {
		return $this->usr_id;
	}


	/**
	 * @param int $usr_id
	 */
	public function setUsrId($usr_id) {
		$this->usr_id = $usr_id;
	}


	/**
	 * @return string
	 */
	public function getMessage() {
		return $this->message;
	}


	/**
	 * @param string $message
	 */
	public function setMessage($message) {
		$this->message = $message;
	}
}