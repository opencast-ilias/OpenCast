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
		$user = new ilObjUser($ilias_user_id);
		$this->setExtId($user->getExternalAccount());
		$this->setFirstName($user->getFirstname());
		$this->setLastName($user->getLastname());
		$this->setEmail($user->getEmail());
	}


	/**
	 * @return string
	 */
	public function getNamePresentation() {
		return $this->getLastName() . ', ' . $this->getFirstName() . ' (' . $this->getEmail() . ')';
	}


	/**
	 * @var int
	 */
	protected $ilias_user_id = 6;
	/**
	 * @var string
	 */
	protected $ext_id;
	/**
	 * @var string
	 */
	protected $first_name = '';
	/**
	 * @var string
	 */
	protected $last_name = '';
	/**
	 * @var string
	 */
	protected $email = '';
	/**
	 * @var int
	 */

	protected $status;


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


	/**
	 * @return string
	 */
	public function getFirstName() {
		return $this->first_name;
	}


	/**
	 * @param string $first_name
	 */
	public function setFirstName($first_name) {
		$this->first_name = $first_name;
	}


	/**
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}


	/**
	 * @param string $email
	 */
	public function setEmail($email) {
		$this->email = $email;
	}


	/**
	 * @return string
	 */
	public function getLastName() {
		return $this->last_name;
	}


	/**
	 * @param string $last_name
	 */
	public function setLastName($last_name) {
		$this->last_name = $last_name;
	}
}