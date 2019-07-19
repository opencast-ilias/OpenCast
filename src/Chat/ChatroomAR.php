<?php

namespace srag\Plugins\Opencast\Chat;

use ActiveRecord;

/**
 * Class ChatroomAR
 *
 * @package srag\Plugins\Opencast\Chat
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class ChatroomAR extends ActiveRecord {

	const TABLE_NAME = 'sr_chat_room';


	/**
	 * @param $event_id
	 * @param $obj_id
	 *
	 * @return ChatroomAR
	 */
	public static function findOrCreate($event_id, $obj_id) {
		$chatroom = self::where(['event_id' => $event_id, 'obj_id' => $obj_id])->first();
		if (!$chatroom) {
			$chatroom = new self();
			$chatroom->setEventId($event_id);
			$chatroom->setObjId($obj_id);
			$chatroom->create();
		}
		return $chatroom;
	}

	/**
	 * @param $event_id
	 * @param $obj_id
	 *
	 * @return ChatroomAR
	 */
	public static function findBy($event_id, $obj_id) {
		$chatroom = self::where(['event_id' => $event_id, 'obj_id' => $obj_id])->first();
		return $chatroom;
	}

	/**
	 * @param $event_id string
	 * @param $obj_id int
	 * @return bool
	 */
	public static function chatroomExists($event_id, $obj_id) {
		return self::where(['event_id' => $event_id, 'obj_id' => $obj_id])->hasSets();
	}

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
	 * @return string
	 */
	public function getEventId() {
		return $this->event_id;
	}


	/**
	 * @param string $event_id
	 */
	public function setEventId($event_id) {
		$this->event_id = $event_id;
	}


	/**
	 * @return int
	 */
	public function getObjId() {
		return $this->obj_id;
	}


	/**
	 * @param int $obj_id
	 */
	public function setObjId($obj_id) {
		$this->obj_id = $obj_id;
	}


}