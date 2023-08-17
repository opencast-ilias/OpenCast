<?php

namespace srag\Plugins\Opencast\Model\Group;

use srag\Plugins\Opencast\Model\API\APIObject;
use srag\Plugins\Opencast\Model\User\xoctUser;
use xoctException;
use srag\Plugins\Opencast\API\OpencastAPI;

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
    protected $roles = [];
    /**
     * @var array
     */
    protected $members = [];
    /**
     * @var String
     */
    protected $name = '';
    /**
     * @var String
     */
    protected $description = '';

    /**
     * @throws xoctException
     */
    public function __construct(string $identifier = '')
    {
        if ($identifier !== '' && $identifier !== '0') {
            $this->setIdentifier($identifier);
            $this->read();
        }
    }

    /**
     * @throws xoctException
     */
    protected function read()
    {
        $data = OpencastAPI::getApi()->groupsApi->get($this->getIdentifier());
        if (!empty($data)) {
            $this->loadFromStdClass($data);
        }
    }

    /**
     * objects xoctUser or uniqueIds as string possible
     *
     *
     * @throws xoctException
     */
    public function addMembers(array $xoctUsers): void
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
     * @throws xoctException
     */
    public function addMember($xoctUser): bool
    {
        if ($xoctUser instanceof xoctUser) {
            $xoctUser = $xoctUser->getIdentifier();
        }

        if ($xoctUser && !in_array($xoctUser, $this->getMembers())) {
            OpencastAPI::getApi()->groupsApi->addMember($this->getIdentifier(), $xoctUser);
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
            default:
                return $value;
        }
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getRole(): string
    {
        return $this->role;
    }


    //	/**
    //	 * @param mixed $role
    //	 */
    //	public function setRole($role) {
    //		$this->role = $role;
    //	}
    public function getOrganization(): string
    {
        return $this->organization;
    }


    //	/**
    //	 * @param mixed $organization
    //	 */
    //	public function setOrganization($organization) {
    //		$this->organization = $organization;
    //	}
    public function getRoles(): array
    {
        return (array) $this->roles;
    }


    //	/**
    //	 * @param mixed $roles
    //	 */
    //	public function setRoles($roles) {
    //		$this->roles = $roles;
    //	}
    public function getMembers(): array
    {
        return (array) $this->members;
    }

    public function setMembers(array $members): void
    {
        $this->members = $members;
    }

    public function getName(): string
    {
        return $this->name;
    }


    //	/**
    //	 * @param mixed $name
    //	 */
    //	public function setName($name) {
    //		$this->name = $name;
    //	}
    public function getDescription(): string
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
