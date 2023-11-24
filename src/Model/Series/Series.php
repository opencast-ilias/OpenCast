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

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getAccessPolicies(): ACL
    {
        return $this->access_policies;
    }

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

    public function getTheme(): int
    {
        return $this->theme;
    }

    public function setTheme(int $theme): void
    {
        $this->theme = $theme;
    }
    
    public function getPermissionTemplateId(): ?int
    {
        $template = PermissionTemplate::getTemplateForAcls($this->getAccessPolicies());
        return $template !== null ? $template->getId() : 0;
    }

    public function isPublishedOnVideoPortal(): bool
    {
        $template = PermissionTemplate::getTemplateForAcls($this->getAccessPolicies());
        return $template !== null && !$template->isDefault();
    }
}
