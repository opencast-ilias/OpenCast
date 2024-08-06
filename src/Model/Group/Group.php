<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Group;

use srag\Plugins\Opencast\Model\API\APIObject;
use srag\Plugins\Opencast\Model\User\xoctUser;
use xoctException;
use srag\Plugins\Opencast\API\API;
use srag\Plugins\Opencast\Container\Init;

/**
 * Class xoctGroup
 *
 * @author  Theodor Truffer <tt@studer-raimann.ch>
 */
class Group extends APIObject
{
    protected API $api;
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
        $opencastContainer = Init::init();
        $this->api = $opencastContainer[API::class];
        if ($identifier !== '' && $identifier !== '0') {
            $this->setIdentifier($identifier);
            $this->read();
        }
    }

    protected function read(): void
    {
        $data = $this->api->routes()->groupsApi->get($this->getIdentifier());
        if (!empty($data)) {
            $this->loadFromStdClass($data);
        }
    }

    /**
     * @param xoctUser[] $xoctUsers
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
    public function addMember(xoctUser $xoctUser): bool
    {
        $user_string = $xoctUser->getIdentifier();

        if (!empty($user_string) && !in_array($user_string, $this->getMembers(), true)) {
            $this->api->routes()->groupsApi->addMember($this->getIdentifier(), $user_string);
            $this->members[] = $user_string;

            return true;
        }

        return false;
    }

    /**
     * @param $fieldname
     * @param $value
     *
     * @return array|mixed
     */
    protected function wakeup($fieldname, $value)
    {
        return match ($fieldname) {
            'members', 'roles' => explode(',', (string) $value),
            default => $value,
        };
    }

    /**
     * @param $fieldname
     * @param $value
     *
     * @return mixed|string
     */
    protected function sleep($fieldname, $value)
    {
        return match ($fieldname) {
            'members', 'roles' => implode(',', $value),
            default => $value,
        };
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

    public function getOrganization(): string
    {
        return $this->organization;
    }

    public function getRoles(): array
    {
        return (array) $this->roles;
    }

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

    public function getDescription(): string
    {
        return $this->description;
    }
}
