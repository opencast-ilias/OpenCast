<?php

namespace srag\Plugins\Opencast\Model\User;

use ilObjUser;
use ilOpenCastPlugin;
use srag\DIC\OpenCast\DICTrait;
use srag\DIC\OpenCast\Exception\DICException;
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
    use DICTrait;

    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

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
     * @throws DICException
     */
    public static function lookupUserIdForOwnerRole($role)
    {
        if (!$role) {
            return null;
        }
        $regex = str_replace('{IDENTIFIER}', '(.*)', PluginConfig::getConfig(PluginConfig::F_ROLE_OWNER_PREFIX));
        $field = self::$user_mapping_field_titles[self::getUserMapping()];

        preg_match("/" . $regex . "/uism", $role, $matches);

        $sql = 'SELECT usr_id FROM usr_data WHERE ' . $field . ' = ' . self::dic()->database()->quote($matches[1], 'text');
        $set = self::dic()->database()->query($sql);
        $data = self::dic()->database()->fetchObject($set);

        return $data->usr_id;
    }

    /**
     * @param ilObjUser|integer $ilUser
     * @return xoctUser
     * @throws xoctException
     */
    public static function getInstance($ilUser)
    {
        $key = (is_numeric($ilUser)) ? $ilUser : $ilUser->getId();
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($key);
        }

        return self::$instances[$key];
    }

    /**
     * @param int $ilias_user_id
     * @throws xoctException
     */
    protected function __construct($ilias_user_id = 6)
    {
        $user = new ilObjUser($ilias_user_id);
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
     * @return string
     */
    public function getNamePresentation($show_email = true)
    {
        return $this->getLastName() . ', ' . $this->getFirstName() . ($show_email ? ' (' . $this->getEmail() . ')' : '');
    }


    /**
     * @return int
     */
    public function getIliasUserId()
    {
        return $this->ilias_user_id;
    }


    /**
     * @param int $ilias_user_id
     */
    public function setIliasUserId($ilias_user_id)
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
    public function setExtId($ext_id)
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
    public function setStatus($status)
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
    public function setFirstName($first_name)
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
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * @param string $login
     */
    public function setLogin(string $login) /*: void*/
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
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
    }

    /**
     * @return int
     * @throws xoctException
     */
    public static function getUserMapping()
    {
        if (!in_array(self::$user_mapping, array_keys(self::$user_mapping_field_titles))) {
            throw new xoctException('invalid user mapping type, id = ' . self::$user_mapping);
        }
        return self::$user_mapping;
    }


    /**
     * @param int $user_mapping
     */
    public static function setUserMapping($user_mapping)
    {
        self::$user_mapping = $user_mapping;
    }


    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return PluginConfig::getConfig(PluginConfig::F_IDENTIFIER_TO_UPPERCASE) ? strtoupper($this->identifier) : $this->identifier;
    }


    /**
     * @return string
     * @throws xoctException
     */
    public function getUserRoleName()
    {
        return $this->getIdentifier() ?
            str_replace('{IDENTIFIER}', $this->getIdentifier(), PluginConfig::getConfig(PluginConfig::F_ROLE_USER_PREFIX))
            : null;
    }


    /**
     * @return string
     */
    public function getOwnerRoleName(): ?string
    {
        if (!$this->getIdentifier()) {
            return null;
        }

        $prefix = PluginConfig::getConfig(PluginConfig::F_ROLE_OWNER_PREFIX);

        return str_replace('{IDENTIFIER}', $this->getIdentifier(), $prefix);
    }


    /**
     * @param string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }
}
