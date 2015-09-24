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
	 * @var int
	 *
	 * @con_has_field  true
	 * @con_fieldtype  integer
	 * @con_length     8
	 */
	protected $obj_id;
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
	 * @param          $series_identifier
	 * @param xoctUser $xoctUser logged in User
	 *
	 * @return xoctInvitation[]
	 */
	public static function getAllInvitationsOfUser($series_identifier, xoctUser $xoctUser) {
		$xoctOpenCast = xoctOpenCast::where(array( 'series_identifier' => $series_identifier ))->last();
		if (! $xoctOpenCast instanceof xoctOpenCast) {
			return array();
		}

		if (! $xoctOpenCast->getPermissionAllowSetOwn()) {
			return array();
		}

		return self::where(array(
			'user_id' => $xoctUser->getIliasUserId(),
			'obj_id' => $xoctOpenCast->getObjId()
		))->get();
	}


	/**
	 * @param $ref_id
	 *
	 * @return xoctGroupParticipant[]
	 * @throws xoctException
	 */
	public static function getAvailable($ref_id) {
		$existing = self::getAllUserIdsForOpenCastObjId(ilObject2::_lookupObjId($ref_id));

		return $existing;

		if (isset(self::$crs_members_cache[$ref_id])) {
			return self::$crs_members_cache[$ref_id];
		}
		global $tree;
		/**
		 * @var $tree ilTree
		 */
		while (ilObject2::_lookupType($ref_id, true) != 'crs') {
			if ($ref_id == 1) {
				throw new xoctException(xoctException::OBJECT_WRONG_PARENT);
			}
			$ref_id = $tree->getParentId($ref_id);
		}

		$p = new ilCourseParticipants(ilObject2::_lookupObjId($ref_id));
		$return = array();
		foreach ($p->getMembers() as $user_id) {
			if (in_array($user_id, $existing)) {
				continue;
			}
			$obj = new self();
			$obj->setUserId($user_id);
			$return[] = $obj;
		}

		self::$crs_members_cache[$ref_id] = $return;

		return $return;
	}


	/**
	 * @param $obj_id
	 *
	 * @return array
	 */
	public function getAllUserIdsForOpenCastObjId($obj_id) {
		$all = xoctGroup::where(array( 'serie_id' => $obj_id ))->getArray(NULL, 'id');
		if (count($all) == 0) {
			return array();
		}

		return self::where(array( 'group_id' => $all ))->getArray(NULL, 'user_id');
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
	public function getObjId() {
		return $this->obj_id;
	}


	/**
	 * @param int $obj_id
	 */
	public function setObjId($obj_id) {
		$this->obj_id = $obj_id;
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