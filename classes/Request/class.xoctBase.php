<?php

declare(strict_types=1);

use srag\Plugins\Opencast\API\API;

/**
 * Class xoctBase
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctBase
{
    /**
     * @var API
     */
    protected $api;
    /**
     * @var array
     */
    protected $api_versions = [];
    /**
     * @var string
     */
    public $api_version;
    /**
     * @var string
     */
    public $organization_id;
    /**
     * @var string
     */
    protected $organization_anonymous_role = '';
    /**
     * @var string
     */
    protected $organization_admin_role = '';
    /**
     * @var string
     */
    protected $organization_name = '';

    public function __construct()
    {
        global $opencastContainer;
        $this->api = $opencastContainer[API::class];
        $version = $this->api->routes()->baseApi->getVersion();
        if (isset($version->default)) {
            $this->setApiVersion($version->default);
        }
        if (isset($version->versions)) {
            $this->setApiVersions($version->versions);
        }

        $org = $this->api->routes()->baseApi->getOrg();
        if (isset($org->id)) {
            $this->setOrganizationId($org->id);
        }
        if (isset($org->anonymousRole)) {
            $this->setOrganizationAnonymousRole($org->anonymousRole);
        }
        if (isset($org->adminRole)) {
            $this->setOrganizationAdminRole($org->adminRole);
        }
        if (isset($org->name)) {
            $this->setOrganizationName($org->name);
        }
    }

    /**
     * @return string
     */
    public function getApiVersion()
    {
        return $this->api_version;
    }

    /**
     * @param string $api_version
     */
    public function setApiVersion($api_version): void
    {
        $this->api_version = $api_version;
    }

    /**
     * @return array
     */
    public function getApiVersions()
    {
        return $this->api_versions;
    }

    /**
     * @param array $api_versions
     */
    public function setApiVersions($api_versions): void
    {
        $this->api_versions = $api_versions;
    }

    /**
     * @return string
     */
    public function getOrganizationId()
    {
        return $this->organization_id;
    }

    /**
     * @param string $organization_id
     */
    public function setOrganizationId($organization_id): void
    {
        $this->organization_id = $organization_id;
    }

    /**
     * @return string
     */
    public function getOrganizationName()
    {
        return $this->organization_name;
    }

    /**
     * @param string $organization_name
     */
    public function setOrganizationName($organization_name): void
    {
        $this->organization_name = $organization_name;
    }

    /**
     * @return string
     */
    public function getOrganizationAnonymousRole()
    {
        return $this->organization_anonymous_role;
    }

    /**
     * @param string $organization_anonymous_role
     */
    public function setOrganizationAnonymousRole($organization_anonymous_role): void
    {
        $this->organization_anonymous_role = $organization_anonymous_role;
    }

    /**
     * @return string
     */
    public function getOrganizationAdminRole()
    {
        return $this->organization_admin_role;
    }

    /**
     * @param string $organization_admin_role
     */
    public function setOrganizationAdminRole($organization_admin_role): void
    {
        $this->organization_admin_role = $organization_admin_role;
    }
}
