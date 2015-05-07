<?php

/**
 * Class xoctUser
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctUser {

	/**
	 * @param int $ilias_user_id
	 */
	public function __construct($ilias_user_id = 6) {
	}


	/**
	 * @var int
	 */
	public $ilias_user_id = 6;
	/**
	 * @var string
	 */
	public $ext_id;
	/**
	 * @var int
	 */
	public $status;


	/**
	 * @return int
	 */
	public function getIliasUserId() {
		return $this->ilias_user_id;
	}


	/**
	 * @param int $ilias_user_id
	 */
	public function setIliasUserId($ilias_user_id) {
		$this->ilias_user_id = $ilias_user_id;
	}


	/**
	 * @return string
	 */
	public function getExtId() {
		return $this->ext_id;
	}


	/**
	 * @param string $ext_id
	 */
	public function setExtId($ext_id) {
		$this->ext_id = $ext_id;
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