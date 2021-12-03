<?php

namespace srag\Plugins\Opencast\Model\Group;

use srag\Plugins\Opencast\Model\API\APIObject;
use xoctException;
use xoctRequest;
use xoctUser;

/**
 * Class xoctGroup
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class Group extends APIObject
{

    /**
     * @var String
     */
    protected $identifier = '';
    /**
     * @var String
     */
    protected $role = '';
    /**
     * @var String
     */
    protected $organization = '';
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
    protected $name = '';
    /**
     * @var String
     */
    protected $description = '';


    /**
     * @param string $identifier
     *
     * @throws xoctException
     */
    public function __construct(string $identifier = '')
    {
        if ($identifier) {
            $this->setIdentifier($identifier);
            $this->read();
        }
    }


    /**
     * @throws xoctException
     */
    protected function read()
    {
        $data = json_decode(xoctRequest::root()->groups($this->getIdentifier())->get());
        $this->loadFromStdClass($data);
    }


    /**
     * objects xoctUser or uniqueIds as string possible
     *
     * @param array $xoctUsers
     *
     * @throws xoctException
     */
    public function addMembers(array $xoctUsers)
    {
        foreach ($xoctUsers as $xoctUser) {
            $this->addMember($xoctUser);
        }
    }


    /**
     * object xoctUser or uniqueId as string possible
     *
     * @param $xoctUser xoctUser|string
     *
     * @return bool
     * @throws xoctException
     */
    public function addMember($xoctUser)
    {
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

    /**
     * @param $fieldname
     * @param $value
     *
     * @return array|mixed
     */
    protected function wakeup($fieldname, $value)
    {
        switch ($fieldname) {
            case 'members':
            case 'roles':
                return explode(',', $value);
                break;
            default:
                return $value;
        }
    }


    /**
     * @param $fieldname
     * @param $value
     *
     * @return mixed|string
     */
    protected function sleep($fieldname, $value)
    {
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
     * @return string
     */
    public function getIdentifier() : string
    {
        return $this->identifier;
    }


    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
    }


    /**
     * @return string
     */
    public function getRole() : string
    {
        return $this->role;
    }


    //	/**
    //	 * @param mixed $role
    //	 */
    //	public function setRole($role) {
    //		$this->role = $role;
    //	}

    /**
     * @return string
     */
    public function getOrganization() : string
    {
        return $this->organization;
    }


    //	/**
    //	 * @param mixed $organization
    //	 */
    //	public function setOrganization($organization) {
    //		$this->organization = $organization;
    //	}

    /**
     * @return array
     */
    public function getRoles() : array
    {
        return (array) $this->roles;
    }


    //	/**
    //	 * @param mixed $roles
    //	 */
    //	public function setRoles($roles) {
    //		$this->roles = $roles;
    //	}

    /**
     * @return array
     */
    public function getMembers() : array
    {
        return (array) $this->members;
    }


    /**
     * @param array $members
     */
    public function setMembers(array $members)
    {
        $this->members = $members;
    }


    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }


    //	/**
    //	 * @param mixed $name
    //	 */
    //	public function setName($name) {
    //		$this->name = $name;
    //	}

    /**
     * @return string
     */
    public function getDescription() : string
    {
        return $this->description;
    }


    //	/**
    //	 * @param mixed $description
    //	 */
    //	public function setDescription($description) {
    //		$this->description = $description;
    //	}

}
