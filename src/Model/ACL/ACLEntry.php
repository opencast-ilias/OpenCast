<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\ACL;

use JsonSerializable;

/**
 * Class xoctAcl
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ACLEntry implements JsonSerializable
{
    public const ADMIN = 'ROLE_ADMIN';
    public const USER = 'ROLE_ADMIN';
    public const WRITE = 'write';
    public const READ = 'read';

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

    public function __construct(string $role, string $action, bool $allow)
    {
        $this->role = $role;
        $this->action = $action;
        $this->allow = $allow;
    }

    public static function fromArray(array $data): self
    {
        return new self($data['role'], $data['action'], $data['allow']);
    }

    /**
     * @return boolean
     */
    public function isAllow()
    {
        return $this->allow;
    }

    /**
     * @param boolean $allow
     */
    public function setAllow($allow): void
    {
        $this->allow = $allow;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction($action): void
    {
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string $role
     */
    public function setRole($role): void
    {
        $this->role = $role;
    }

    /**
     * @return array{role: string, action: string, allow: bool}
     */
    public function jsonSerialize(): mixed
    {
        return [
            'role' => $this->getRole(),
            'action' => $this->getAction(),
            'allow' => $this->isAllow()
        ];
    }
}
