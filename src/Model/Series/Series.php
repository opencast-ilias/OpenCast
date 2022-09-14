<?php

namespace srag\Plugins\Opencast\Model\Series;

use srag\Plugins\Opencast\Model\ACL\ACL;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\PermissionTemplate\PermissionTemplate;

/**
 * Class Series
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class Series
{
    /**
     * @var string
     */
    protected $identifier;
    /**
     * @var ACL
     */
    public $access_policies;
    /**
     * @var Metadata
     */
    protected $metadata = [];
    /**
     * @var int
     */
    protected $theme;

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     */
    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * @return ACL
     */
    public function getAccessPolicies(): ACL
    {
        return $this->access_policies;
    }

    /**
     * @param ACL $access_policies
     */
    public function setAccessPolicies(ACL $access_policies): void
    {
        $this->access_policies = $access_policies;
    }

    public function getMetadata(): Metadata
    {
        return $this->metadata;
    }

    /**
     * @param Metadata $metadata
     */
    public function setMetadata($metadata): void
    {
        $this->metadata = $metadata;
    }

    /**
     * @return int
     */
    public function getTheme(): int
    {
        return $this->theme;
    }

    /**
     * @param int $theme
     */
    public function setTheme(int $theme): void
    {
        $this->theme = $theme;
    }

    /**
     * @return int
     */
    public function getPermissionTemplateId()
    {
        $template = PermissionTemplate::getTemplateForAcls($this->getAccessPolicies());
        return $template ? $template->getId() : 0;
    }

    /**
     * @return bool
     */
    public function isPublishedOnVideoPortal(): bool
    {
        $template = PermissionTemplate::getTemplateForAcls($this->getAccessPolicies());
        return $template && !$template->isDefault();
    }
}
