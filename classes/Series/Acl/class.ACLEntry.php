<?php

/**
 * Class xoctAcl
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ACLEntry implements JsonSerializable {

	const ADMIN = 'ROLE_ADMIN';
	const USER = 'ROLE_ADMIN';
	const WRITE = 'write';
	const READ = 'read';


    /**
     * @var string
     */
    public $role;
    /**
     * @var string
     */
    public $action;
    /**
	 * @var bool
	 */
	public $allow = false;

    /**
     * @param string $role
     * @param string $action
     * @param bool $allow
     */
    public function __construct(string $role, string $action, bool $allow)
    {
        $this->role = $role;
        $this->action = $action;
        $this->allow = $allow;
    }


    public static function fromArray(array $data) : self
    {
        return new self($data['role'], $data['action'], $data['allow']);
    }

	/**
	 * @return boolean
	 */
	public function isAllow() {
		return $this->allow;
	}


	/**
	 * @param boolean $allow
	 */
	public function setAllow($allow) {
		$this->allow = $allow;
	}


	/**
	 * @return string
	 */
	public function getAction() {
		return $this->action;
	}


	/**
	 * @param string $action
	 */
	public function setAction($action) {
		$this->action = $action;
	}


	/**
	 * @return string
	 */
	public function getRole() {
		return $this->role;
	}


	/**
	 * @param string $role
	 */
	public function setRole($role) {
		$this->role = $role;
	}

    public function jsonSerialize()
    {
        return [
            'role' => $this->getRole(),
            'action' => $this->getAction(),
            'allow' => $this->isAllow()
        ];
    }
}
