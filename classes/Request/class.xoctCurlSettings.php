<?php

/**
 * Class xoctCurlSettings
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctCurlSettings
{
    /**
     * @var bool
     */
    protected $ip_v4 = false;
    /**
     * @var bool
     */
    protected $verify_host = false;
    /**
     * @var bool
     */
    protected $verify_peer = false;
    /**
     * @var string
     */
    protected $username;
    /**
     * @var string
     */
    protected $password;

    /**
     * @return boolean
     */
    public function isIpV4()
    {
        return $this->ip_v4;
    }

    /**
     * @param boolean $ip_v4
     */
    public function setIpV4($ip_v4): void
    {
        $this->ip_v4 = $ip_v4;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username): void
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password): void
    {
        $this->password = $password;
    }
}
