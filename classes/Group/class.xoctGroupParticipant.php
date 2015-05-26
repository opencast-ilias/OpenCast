<?php
require_once('./Services/ActiveRecord/class.ActiveRecord.php');

/**
 * Class xoctGroupParticipant
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctGroupParticipant extends ActiveRecord {

	const STATUS_ACTIVE = 1;


	/**
	 * @return string
	 */
	static function returnDbTableName() {
		return 'xoct_group_participant';
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
	protected $user_id;
	/**
	 * @var int
	 *
	 * @con_has_field  true
	 * @con_fieldtype  integer
	 * @con_length     8
	 */
	protected $group_id;
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
	protected static $crs_members_cache = array();


	/**
	 * @param $ref_id
	 *
	 * @return xoctGroupParticipant[]
	 * @throws xoctExeption
	 */
	public static function getAvailable($ref_id) {
		//		echo '<pre>' . print_r(, 1) . '</pre>';
		//		exit;

		$existing = self::getAllUserIdsForOpenCastObjId(ilObject2::_lookupObjId($ref_id));

		if (isset(self::$crs_members_cache[$ref_id])) {
			return self::$crs_members_cache[$ref_id];
		}
		global $tree;
		/**
		 * @var $tree ilTree
		 */
		while (ilObject2::_lookupType($ref_id, true) != 'crs') {
			if ($ref_id == 1) {
				throw new xoctExeption(xoctExeption::OBJECT_WRONG_PARENT);
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
	public function getGroupId() {
		return $this->group_id;
	}


	/**
	 * @param $group_id
	 */
	public function setGroupId($group_id) {
		$this->group_id = $group_id;
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
	 * @return xoctUser
	 */
	public function getXoctUser() {
		if (! $this->xoct_user AND $this->getUserId()) {
			$this->xoct_user = new xoctUser($this->getUserId());
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