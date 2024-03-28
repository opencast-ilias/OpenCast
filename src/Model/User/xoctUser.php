<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\User;

use ilObjUser;
use ilOpenCastPlugin;
use srag\Plugins\Opencast\Model\Config\PluginConfig;
use xoctException;

/**
 * Class xoctUser
 * kind of a wrapper for user-related functions, like building user-specific role names
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctUser
{
    public const MAP_EMAIL = 1;
    public const MAP_EXT_ID = 2;
    public const MAP_LOGIN = 3;

    protected static $user_mapping_field_titles = [
        self::MAP_EMAIL => 'email',
        self::MAP_EXT_ID => 'ext_account',
        self::MAP_LOGIN => 'login',
    ];
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
     * @var string
     */
    protected $login = '';
    /**
     * @var int
     */
    protected $status;
    /**
     * @var xoctUser[]
     */
    protected static $instances = [];

    /**
     * @return mixed
     */
    public static function getOwnerRolePrefix()
    {
        return PluginConfig::getConfig(PluginConfig::F_ROLE_OWNER_PREFIX);
    }

    /**
     * @param $role
     * @return int
     * @throws xoctException
     */
    public static function lookupUserIdForOwnerRole(string $role): ?int
    {
        global $DIC;
        $db = $DIC->database();
        if (!$role) {
            return null;
        }
        $regex = str_replace('{IDENTIFIER}', '(.*)', PluginConfig::getConfig(PluginConfig::F_ROLE_OWNER_PREFIX));
        $field = self::$user_mapping_field_titles[self::getUserMapping()];

        preg_match("/" . $regex . "/uism", $role, $matches);

        $sql = 'SELECT usr_id FROM usr_data WHERE ' . $field . ' = ' . $db->quote($matches[1], 'text');
        $set = $db->query($sql);

        $usr_id = $db->fetchObject($set)->usr_id ?? null;
        return $usr_id === null ? null : (int) $usr_id;
    }

    /**
     * @param ilObjUser|int|numeric-string $ilUser
     * @throws xoctException
     */
    public static function getInstance($ilUser): self
    {
        $key = (is_numeric($ilUser)) ? (int) $ilUser : $ilUser->getId();
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($key);
        }

        return self::$instances[$key];
    }

    /**
     * @throws xoctException
     */
    protected function __construct(int $ilias_user_id = 6)
    {
        $user = new ilObjUser($ilias_user_id);
        if (is_null($user->getLogin())) {
            return;
        }
        $this->setIliasUserId($ilias_user_id);
        $this->setExtId($user->getExternalAccount());
        $this->setFirstName($user->getFirstname());
        $this->setLastName($user->getLastname());
        $this->setEmail($user->getEmail());
        $this->setLogin($user->getLogin());
        switch (self::getUserMapping()) {
            case self::MAP_EXT_ID:
                $this->setIdentifier($this->getExtId());
                break;
            case self::MAP_EMAIL:
                $this->setIdentifier($this->getEmail());
                break;
            case self::MAP_LOGIN:
                $this->setIdentifier($this->getLogin());
                break;
        }
    }

    /**
     * @param bool $show_email
     */
    public function getNamePresentation($show_email = true): string
    {
        return $this->getLastName() . ', ' . $this->getFirstName() . ($show_email ? ' (' . $this->getEmail(
                ) . ')' : '');
    }

    public function getIliasUserId(): int
    {
        return (int) $this->ilias_user_id;
    }

    public function setIliasUserId(int $ilias_user_id): void
    {
        $this->ilias_user_id = $ilias_user_id;
    }

    /**
     * @return string
     */
    public function getExtId()
    {
        return $this->ext_id;
    }

    /**
     * @param string $ext_id
     */
    public function setExtId($ext_id): void
    {
        $this->ext_id = $ext_id;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * @param string $first_name
     */
    public function setFirstName($first_name): void
    {
        $this->first_name = $first_name;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * @param string $last_name
     */
    public function setLastName($last_name): void
    {
        $this->last_name = $last_name;
    }

    /**
     * @return int
     * @throws xoctException
     */
    public static function getUserMapping()
    {
        if (!array_key_exists(self::$user_mapping, self::$user_mapping_field_titles)) {
            throw new xoctException('invalid user mapping type, id = ' . self::$user_mapping);
        }
        return self::$user_mapping;
    }


    public static function setUserMapping(int $user_mapping): void
    {
        self::$user_mapping = $user_mapping;
    }

    public function getIdentifier(): string
    {
        return PluginConfig::getConfig(PluginConfig::F_IDENTIFIER_TO_UPPERCASE) ? strtoupper(
            $this->identifier
        ) : $this->identifier;
    }

    /**
     * @return string
     */
    public function getUserRoleName(): ?string
    {
        return !empty($this->getIdentifier()) ?
            str_replace(
                '{IDENTIFIER}',
                $this->getIdentifier(),
                PluginConfig::getConfig(PluginConfig::F_ROLE_USER_PREFIX)
            )
            : null;
    }

    public function getOwnerRoleName(): ?string
    {
        if ($this->getIdentifier() === '' || $this->getIdentifier() === '0') {
            return null;
        }

        $prefix = PluginConfig::getConfig(PluginConfig::F_ROLE_OWNER_PREFIX);

        return str_replace('{IDENTIFIER}', $this->getIdentifier(), $prefix);
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier($identifier): void
    {
        $this->identifier = $identifier;
    }
}
