<?php

/**
 * Class xoctGroupParticipant
 */
class xoctGroupParticipant {

	/**
	 * @param int $id
	 */
	public function __construct($id = 0) {
	}


	/**
	 * @var int
	 */
	public $id = 0;
	/**
	 * @var int
	 */
	public $user_id;
	/**
	 * @var int
	 */
	public $groupe_id;
	/**
	 * @var int
	 */
	public $status;


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
	public function getUserId() {
		return $this->user_id;
	}


	/**
	 * @param int $user_id
	 */
	public function setUserId($user_id) {
		$this->user_id = $user_id;
	}


	/**
	 * @return int
	 */
	public function getGroupeId() {
		return $this->groupe_id;
	}


	/**
	 * @param int $groupe_id
	 */
	public function setGroupeId($groupe_id) {
		$this->groupe_id = $groupe_id;
	}


	/**
	 * @return int
	 */
	public function getStatus() {
		return $this->status;
	}


	/**
	 * @param int $status
	 */
	public function setStatus($status) {
		$this->status = $status;
	}
}