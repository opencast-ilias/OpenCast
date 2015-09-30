<?php
require_once('./Services/ActiveRecord/class.ActiveRecord.php');

/**
 * Class xoctInvitation
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctInvitation extends ActiveRecord {

	const STATUS_ACTIVE = 1;


	/**
	 * @return string
	 */
	static function returnDbTableName() {
		return 'xoct_invitations';
	}


	/**
	 * @var int
	 *
	 * @con_is_primary true
	 * @con_is_unique  true
	 * @con_has_field  true
	 * @con_fieldtype  integer
	 * @con_length     8
	 * @con_sequence   true
	 */
	protected $id = 0;
	/**
	 * @var string
	 *
	 * @con_has_field  true
	 * @con_fieldtype  text
	 * @con_length     128
	 */
	protected $event_identifier;
	/**
	 * @var int
	 *
	 * @con_has_field  true
	 * @con_fieldtype  integer
	 * @con_length     8
	 */
	protected $user_id;
	/**
	 * @var int
	 *
	 * @con_has_field  true
	 * @con_fieldtype  integer
	 * @con_length     8
	 */
	protected $owner_id;
	/**
	 * @var xoctUser
	 */
	protected $xoct_user = NULL;
	/**
	 * @var int
	 *
	 * @con_has_field  true
	 * @con_fieldtype  integer
	 * @con_length     1
	 */
	protected $status = self::STATUS_ACTIVE;
	/**
	 * @var array
	 */
	protected static $series_id_to_groups_map = array();


	/**
	 * @param          $event_identifier
	 * @param xoctUser $xoctUser
	 *
	 * @return array
	 */
	public static function getAllInvitationsOfUser($event_identifier, xoctUser $xoctUser) {
		return self::where(array(
			'user_id' => $xoctUser->getIliasUserId(),
			'event_identifier' => $event_identifier
		))->get();
	}




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
	public function getOwnerId() {
		return $this->owner_id;
	}


	/**
	 * @param int $owner_id
	 */
	public function setOwnerId($owner_id) {
		$this->owner_id = $owner_id;
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
	 * @return int
	 */
	public function getEventIdentifier() {
		return $this->event_identifier;
	}


	/**
	 * @param int $event_identifier
	 */
	public function setEventIdentifier($event_identifier) {
		$this->event_identifier = $event_identifier;
	}


	/**
	 * @return xoctUser
	 */
	public function getXoctUser() {
		if (! $this->xoct_user AND $this->getUserId()) {
			$this->xoct_user = xoctUser::getInstance(new ilObjUser($this->getUserId()));
		}

		return $this->xoct_user;
	}


	/**
	 * @param xoctUser $xoct_user
	 */
	public function setXoctUser($xoct_user) {
		$this->xoct_user = $xoct_user;
	}
}