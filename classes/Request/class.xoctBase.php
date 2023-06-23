<?php
/**
 * Class xoctBase
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctBase
{
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
        $version = xoctRequest::root()->base()->version()->get();
        if (isset($version->default)) {
            $this->setApiVersion($version->default);
        }
        if (isset($version->versions)) {
            $this->setApiVersions($version->versions);
        }

        $org = xoctRequest::root()->organization()->get();
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
    public function setApiVersion($api_version)
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
    public function setApiVersions($api_versions)
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
    public function setOrganizationId($organization_id)
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
    public function setOrganizationName($organization_name)
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
    public function setOrganizationAnonymousRole($organization_anonymous_role)
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
    public function setOrganizationAdminRole($organization_admin_role)
    {
        $this->organization_admin_role = $organization_admin_role;
    }
}
