<?php

namespace srag\Plugins\Opencast\Chat\Model;


use ActiveRecord;

/**
 * Class EventAR
 * @package srag\Plugins\Opencast\Chat
 *
 * @author Theodor Truffer <tt@studer-raimann.ch>
 */
class EventAR extends ActiveRecord {

	const TABLE_NAME = 'sr_chat_event';

	const EVENT_ID_USER_JOINED = 1;
	const EVENT_ID_USER_LEFT = 2;


	/**
	 * @return string
	 */
	public function getConnectorContainerName() {
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
	 * @db_fieldtype        integer
	 * @db_length           8
	 */
	protected $subject_id;

	/**
	 * @var int
	 *
	 * @db_has_field        true
	 * @db_is_notnull       true
	 * @db_fieldtype        integer
	 * @db_length           8
	 */
	protected $event_type_id;

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
	public function getSubjectId() {
		return $this->subject_id;
	}


	/**
	 * @param int $subject_id
	 */
	public function setSubjectId($subject_id) {
		$this->subject_id = $subject_id;
	}

	/**
	 * @return int
	 */
	public function getEventTypeId(): int {
		return $this->event_type_id;
	}

	/**
	 * @param int $event_type_id
	 */
	public function setEventTypeId(int $event_type_id) {
		$this->event_type_id = $event_type_id;
	}


	/**
	 * @return string
	 */
	public function getSentAt() {
		return $this->sent_at;
	}


	/**
	 * @param string $sent_at
	 */
	public function setSentAt($sent_at) {
		$this->sent_at = $sent_at;
	}
}