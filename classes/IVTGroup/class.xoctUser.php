<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/class.xoctConf.php');

/**
 * Class xoctUser
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctUser {

	const MAP_EMAIL = 1;
	const MAP_EXT_ID = 2;
	/**
	 * @var int
	 */
	protected static $user_mapping = self::MAP_EXT_ID;
	/**
	 * @var string
	 */
	protected $identifier = '';
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
	 * @var xoctUser[]
	 */
	protected static $instances = array();


	/**
	 * @return mixed
	 */
	public static function getOwnerRolePrefix() {
		switch (self::getUserMapping()) {
			case self::MAP_EXT_ID:
				return xoctConf::get(xoctConf::F_ROLE_OWNER_EXTERNAL_PREFIX);
				break;
			case self::MAP_EMAIL:
				return xoctConf::get(xoctConf::F_ROLE_OWNER_EMAIL_PREFIX);
				break;
		}
	}


	/**
	 * @param $role
	 *
	 * @return int
	 */
	public static function lookupUserIdForOwnerRole($role) {
		if (!$role) {
			return NULL;
		}
		switch (self::getUserMapping()) {
			case self::MAP_EXT_ID:
				$regex = str_replace('{IDENTIFIER}', '(.*)', xoctConf::get(xoctConf::F_ROLE_OWNER_EXTERNAL_PREFIX));
				$field = 'ext_account';
				break;
			case self::MAP_EMAIL:
				$regex = str_replace('{IDENTIFIER}', '(.*)', xoctConf::get(xoctConf::F_ROLE_OWNER_EMAIL_PREFIX));
				$field = 'email';
				break;
		}

		preg_match("/" . $regex . "/uism", $role, $matches);

		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$sql = 'SELECT usr_id FROM usr_data WHERE ' . $field . ' = ' . $ilDB->quote($matches[1], 'text');
		$set = $ilDB->query($sql);
		$data = $ilDB->fetchObject($set);

		return $data->usr_id;
	}


	/**
	 * @param ilObjUser|integer $ilUser
	 *
	 * @return xoctUser
	 */
	public static function getInstance($ilUser) {
		$key = (is_numeric($ilUser)) ? $ilUser : $ilUser->getId();
		if (!isset(self::$instances[$key])) {
			self::$instances[$key] = new self($key);
		}

		return self::$instances[$key];
	}


	/**
	 * @param int $ilias_user_id
	 */
	protected function __construct($ilias_user_id = 6) {
		$user = new ilObjUser($ilias_user_id);
		$this->setIliasUserId($ilias_user_id);
		$this->setExtId($user->getExternalAccount());
		$this->setFirstName($user->getFirstname());
		$this->setLastName($user->getLastname());
		$this->setEmail($user->getEmail());
		switch (self::getUserMapping()) {
			case self::MAP_EXT_ID:
				$this->setIdentifier($this->getExtId());
				break;
			case self::MAP_EMAIL:
				$this->setIdentifier($this->getEmail());
				break;
		}
	}


	/**
	 * @return string
	 */
	public function getNamePresentation() {
		return $this->getLastName() . ', ' . $this->getFirstName() . ' (' . $this->getEmail() . ')';
	}


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


	/**
	 * @return int
	 */
	public static function getUserMapping() {
		return self::$user_mapping;
	}


	/**
	 * @param int $user_mapping
	 */
	public static function setUserMapping($user_mapping) {
		self::$user_mapping = $user_mapping;
	}


	/**
	 * @return string
	 * @throws xoctException
	 */
	public function getIdentifier() {
		if (!$this->identifier) {
			//			throw new xoctException(xoctException::NO_USER_MAPPING);
		}

		return $this->identifier;
	}


	/**
	 * @param $string
	 *
	 * @return mixed
	 */
	protected function modify($string) {
		return $string;
	}


	/**
	 * @return string
	 */
	public function getUserRoleName() {
		return str_replace('{IDENTIFIER}', $this->modify($this->getIdentifier()), xoctConf::get(xoctConf::F_ROLE_USER_PREFIX));
	}


	/**
	 * @return string
	 * @throws xoctException
	 */
	public function getOwnerRoleName() {
		switch (self::getUserMapping()) {
			case self::MAP_EXT_ID:
				$prefix = xoctConf::get(xoctConf::F_ROLE_OWNER_EXTERNAL_PREFIX);
				break;
			default:
				$prefix = xoctConf::get(xoctConf::F_ROLE_OWNER_EMAIL_PREFIX);
				break;
		}
		if (!$prefix) {
			//			throw new xoctException(xoctException::NO_USER_MAPPING);
		}

		return str_replace('{IDENTIFIER}', $this->modify($this->getIdentifier()), $prefix);
	}


	/**
	 * @param string $identifier
	 */
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}
}