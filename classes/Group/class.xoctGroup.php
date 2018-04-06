<?php
/**
 * Class xoctGroup
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class xoctGroup extends xoctObject {

	/**
	 * @var String
	 */
	protected $identifier;
	/**
	 * @var String
	 */
	protected $role;
	/**
	 * @var String
	 */
	protected $organization;
	/**
	 * @var array
	 */
	protected $roles = array();
	/**
	 * @var array
	 */
	protected $members = array();
	/**
	 * @var String
	 */
	protected $name;
	/**
	 * @var String
	 */
	protected $description;

	/**
	 * @param string $identifier
	 */
	public function __construct($identifier = '') {
		if ($identifier) {
			$this->setIdentifier($identifier);
			$this->read();
		}
	}

	protected function read() {
		$data = json_decode(xoctRequest::root()->groups($this->getIdentifier())->get());
		$this->loadFromStdClass($data);
	}


	/**
	 * objects xoctUser or uniqueIds as string possible
	 *
	 * @param array $xoctUsers
	 */
	public function addMembers(array $xoctUsers) {
		foreach ($xoctUsers as $xoctUser) {
			$this->addMember($xoctUser);
		}
	}


	/**
	 * object xoctUser or uniqueId as string possible
	 *
	 * @param $xoctUser|string
	 */
	public function addMember($xoctUser) {
		if ($xoctUser instanceof xoctUser) {
			$xoctUser = $xoctUser->getIdentifier();
		}

		if ($xoctUser && !in_array($xoctUser, $this->getMembers())) {
			xoctRequest::root()->groups($this->getIdentifier())->members()->post(array('member' => $xoctUser));
			$this->members[] = $xoctUser;
			return true;
		}

		return false;
	}


//	/**
//	 * only allow changes on members for now, so we don't break anything
//	 */
//	public function update() {
//		$data['members'] = json_encode(array($this->getMembers()->__toStdClass()));
//		xoctRequest::root()->groups($this->getIdentifier())->put($data);
//		self::removeFromCache($this->getIdentifier());
//	}


	protected function wakeup($fieldname, $value) {
		switch ($fieldname) {
			case 'members':
			case 'roles':
				return explode(',', $value);
				break;
			default:
				return $value;
		}
	}


	protected function sleep($fieldname, $value) {
		switch ($fieldname) {
			case 'members':
			case 'roles':
				return implode(',', $value);
				break;
			default:
				return $value;
		}
	}


	/**
	 * @return mixed
	 */
	public function getIdentifier() {
		return $this->identifier;
	}


	/**
	 * @param mixed $identifier
	 */
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}


	/**
	 * @return mixed
	 */
	public function getRole() {
		return $this->role;
	}


//	/**
//	 * @param mixed $role
//	 */
//	public function setRole($role) {
//		$this->role = $role;
//	}


	/**
	 * @return mixed
	 */
	public function getOrganization() {
		return $this->organization;
	}


//	/**
//	 * @param mixed $organization
//	 */
//	public function setOrganization($organization) {
//		$this->organization = $organization;
//	}


	/**
	 * @return mixed
	 */
	public function getRoles() {
		return (array) $this->roles;
	}


//	/**
//	 * @param mixed $roles
//	 */
//	public function setRoles($roles) {
//		$this->roles = $roles;
//	}


	/**
	 * @return mixed
	 */
	public function getMembers() {
		return (array) $this->members;
	}


	/**
	 * @param mixed $members
	 */
	public function setMembers($members) {
		$this->members = $members;
	}


	/**
	 * @return mixed
	 */
	public function getName() {
		return $this->name;
	}


//	/**
//	 * @param mixed $name
//	 */
//	public function setName($name) {
//		$this->name = $name;
//	}


	/**
	 * @return mixed
	 */
	public function getDescription() {
		return $this->description;
	}


//	/**
//	 * @param mixed $description
//	 */
//	public function setDescription($description) {
//		$this->description = $description;
//	}



}
