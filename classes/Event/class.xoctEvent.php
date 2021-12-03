<?php

use Opis\Closure\SerializableClosure;
use srag\Plugins\Opencast\Model\ACL\ACL;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository;
use srag\Plugins\Opencast\Model\Publication\PublicationSelector;
use srag\Plugins\Opencast\Model\Scheduling\Scheduling;
use srag\Plugins\Opencast\Model\WorkflowInstance\WorkflowInstanceCollection;

/**
 * Class xoctEvent
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctEvent
{

    const STATE_SUCCEEDED = 'SUCCEEDED';
    const STATE_OFFLINE = 'OFFLINE';
    const STATE_SCHEDULED = 'SCHEDULED';
    const STATE_SCHEDULED_OFFLINE = 'SCHEDULED_OFFLINE';
    const STATE_INSTANTIATED = 'INSTANTIATED';
    const STATE_ENCODING = 'RUNNING';
    const STATE_RECORDING = 'RECORDING';
    const STATE_NOT_PUBLISHED = 'NOT_PUBLISHED';
    const STATE_READY_FOR_CUTTING = 'READY_FOR_CUTTING';
    const STATE_FAILED = 'FAILED';
    const STATE_LIVE_SCHEDULED = 'LIVE_SCHEDULED';
    const STATE_LIVE_RUNNING = 'LIVE_RUNNING';
    const STATE_LIVE_OFFLINE = 'LIVE_OFFLINE';

    /**
     * @var array
     *
     * used for colouring
     */
    public static $state_mapping = array(
        xoctEvent::STATE_SUCCEEDED => 'success',
        xoctEvent::STATE_INSTANTIATED => 'info',
        xoctEvent::STATE_ENCODING => 'info',
        xoctEvent::STATE_RECORDING => 'info',
        xoctEvent::STATE_NOT_PUBLISHED => 'info',
        xoctEvent::STATE_READY_FOR_CUTTING => 'info',
        xoctEvent::STATE_SCHEDULED => 'scheduled',
        xoctEvent::STATE_SCHEDULED_OFFLINE => 'scheduled',
        xoctEvent::STATE_FAILED => 'danger',
        xoctEvent::STATE_OFFLINE => 'info',
        xoctEvent::STATE_LIVE_SCHEDULED => 'scheduled',
        xoctEvent::STATE_LIVE_RUNNING => 'info',
        xoctEvent::STATE_LIVE_OFFLINE => 'info',
    );
    /**
     * @var PublicationSelector
     */
    protected $publications;
    /**
     * @var xoctEventAdditions
     */
    protected $xoctEventAdditions = null;
    /**
     * @var SerializableClosure
     */
    private $metadata_reference;
    /**
     * @var SerializableClosure
     */
    private $acl_reference;
    /**
     * @var string
     */
    private $status;
    /**
     * @var SerializableClosure
     */
    private $scheduling_reference;

    /**
     * @var bool
     */
    protected $has_previews;
    /**
     * @var array
     */
    protected $publication_status;
    /**
     * @var String
     */
    protected $processing_state;
    /**
     * @var Metadata
     */
    protected $metadata;
    /**
     * @var ACL
     */
    protected $acl;
    /**
     * @var ?Scheduling
     */
    protected $scheduling;
    /**
     * @var WorkflowInstanceCollection
     */
    protected $workflows;
    /**
     * @var string
     */
    protected $series;

    /**
     * @var bool
     */
    protected $processing_state_init = false;

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }


    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }


    /**
     * @return array
     */
    public function getArrayForTable()
    {
        return array(
            'identifier' => $this->getIdentifier(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'presenter' => $this->getPresenter(),
            'location' => $this->getLocation(),
            'created' => $this->getCreated()->format(DATE_ATOM),
            'created_unix' => $this->getCreated()->format('U'),
            'start' => $this->getStart()->format(DATE_ATOM),
            'start_unix' => $this->getStart()->format('U'),
            'owner_username' => $this->getOwnerUsername(),
            'processing_state' => $this->getProcessingState(),
            'object' => $this,
        );
    }


    /**
     * @param xoctUser $xoctUser
     *
     * @return bool
     * @throws xoctException
     */
    public function hasWriteAccess(xoctUser $xoctUser)
    {
        if ($this->isOwner($xoctUser)) {
            return true;
        }

        return false;
    }

    /**
     * @param xoctUser $xoctUser
     * @return bool
     * @throws xoctException
     */
    public function isOwner(xoctUser $xoctUser)
    {
        $xoctAcl = $this->getOwnerAcl();
        if (!$xoctAcl instanceof ACLEntry) {
            return false;
        }
        if ($xoctAcl->getRole() == $xoctUser->getOwnerRoleName()) {
            return true;
        }
    }


    /**
     * @return null|ACLEntry
     */
    public function getOwnerAcl()
    {
        static $owner_acl;
        if (isset($owner_acl[$this->getIdentifier()])) {
            return $owner_acl[$this->getIdentifier()];
        }
        foreach ($this->getAcl()->getEntries() as $acl_entry) {
            if (strpos($acl_entry->getRole(), str_replace('{IDENTIFIER}', '', xoctUser::getOwnerRolePrefix())) !== false) {
                $owner_acl[$this->getIdentifier()] = $acl_entry;

                return $acl_entry;
            }
        }
        $owner_acl[$this->getIdentifier()] = null;

        return null;
    }


    /**
     * @return null|xoctUser
     */
    public function getOwner()
    {
        $acl = $this->getOwnerAcl();
        if ($acl instanceof ACLEntry) {
            $usr_id = xoctUser::lookupUserIdForOwnerRole($acl->getRole());
            if ($usr_id) {
                return xoctUser::getInstance(new ilObjUser($usr_id));
            }
        } else {
            return null;
        }
    }


    /**
     * @return bool
     * @throws xoctException
     */
    public function unpublish()
    {
        $workflow = xoctConf::getConfig(xoctConf::F_WORKFLOW_UNPUBLISH);
        xoctRequest::root()->workflows()->post(array(
            'workflow_definition_identifier' => $workflow,
            'event_identifier' => $this->getIdentifier()
        ));
//        self::removeFromCache($this->getIdentifier());
        return true;
    }

    /**
     *
     */
    public function loadWorkflows()
    {
        if ($this->getIdentifier()) {
            $this->workflows = new WorkflowInstanceCollection($this->getIdentifier());
        } else {
            $this->workflows = new WorkflowInstanceCollection();
        }
    }

    protected function initProcessingState() : void
    {
        // todo: think this over
        if (!$this->getIdentifier()) {
            return;
        }
        if ($this->processing_state_init) {
            return;
        }
        if ($this->status == 'EVENTS.EVENTS.STATUS.PROCESSED') {
            $this->processing_state = 'SUCCEEDED';
        }
        switch ($this->processing_state) {
            case self::STATE_SUCCEEDED:
                if (!$this->getXoctEventAdditions()->getIsOnline()) {
                    $this->setProcessingState(self::STATE_OFFLINE);
                } else {
                    $publication_player = (new PublicationUsageRepository())->getUsage(PublicationUsage::USAGE_PLAYER);

                    // "not published" depends: if the internal player is used, the "api" publication must be present, else the "player" publication
                    if (!in_array($publication_player->getChannel(), $this->publication_status)) {
                        if ($this->hasPreviews()) {
                            $this->setProcessingState(self::STATE_READY_FOR_CUTTING);
                        } else {
                            $this->setProcessingState(self::STATE_NOT_PUBLISHED);
                        }
                    }
                }
                break;
            case '': // empty state means it's a scheduled event
                if ($this->status == 'EVENTS.EVENTS.STATUS.RECORDING') {
                    $this->setProcessingState($this->isLiveEvent() ? self::STATE_LIVE_RUNNING : self::STATE_RECORDING);
                } elseif (!$this->getXoctEventAdditions()->getIsOnline()) {
                    $this->setProcessingState($this->isLiveEvent() ? self::STATE_LIVE_OFFLINE : self::STATE_SCHEDULED_OFFLINE);
                } else {
                    $this->setProcessingState($this->isLiveEvent() ? self::STATE_LIVE_SCHEDULED : self::STATE_SCHEDULED);
                }
                break;
        }

        $this->processing_state_init = true;
    }

    public function getStart(): DateTimeImmutable
    {
        return $this->getMetadata()->getField('startDate')->getValue();
    }


    /**
     * this should only be called on scheduled events
     */
    public function getEnd(): DateTimeImmutable
    {
        return $this->getMetadata()->getField('end')->getValue();
    }

    public function getSeries(): string
    {
        return $this->getMetadata()->getField(MDFieldDefinition::F_IS_PART_OF)->getValue();
    }

    public function getIdentifier(): string
    {
        return $this->getMetadata()->getField('identifier')->getValue();
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->getMetadata()->getField('created')->getValue();
    }

    public function getCreator(): string
    {
        return $this->getMetadata()->getField('creator')->getValue();
    }

    public function getDescription(): string
    {
        return $this->getMetadata()->getField('description')->getValue();
    }

    public function hasPreviews(): bool
    {
        return $this->has_previews;
    }

    public function setHasPreviews(bool $has_previews): void
    {
        $this->has_previews = $has_previews;
    }

    public function getLocation(): string
    {
        return $this->getMetadata()->getField('location')->getValue();
    }

    public function getPresenter(): array
    {
        return $this->getMetadata()->getField('creator')->getValue();
    }

    public function getPublicationStatus(): array
    {
        return $this->publication_status;
    }

    public function setPublicationStatus(array $publication_status): void
    {
        $this->publication_status = $publication_status;
    }

    public function getProcessingState(): string
    {
        $this->initProcessingState();
        return $this->processing_state;
    }


    public function setProcessingState(string $processing_state): void
    {
        $this->processing_state = $processing_state;
    }

    public function getTitle(): string
    {
        return $this->getMetadata()->getField('title')->getValue();
    }

    public function getMetadata(): Metadata
    {
        if (!$this->metadata) {
            $reference = $this->metadata_reference->getClosure();
            $this->metadata = $reference();
        }
        return $this->metadata;
    }

    /**
     * @return PublicationSelector
     */
    public function publications(): PublicationSelector
    {
        if (!$this->publications) {
            $this->publications = new PublicationSelector($this);
        }
        return $this->publications;
    }

    public function setMetadata(Metadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getAcl(): ACL
    {
        if (!$this->acl) {
            $reference = $this->acl_reference->getClosure();
            $this->acl = $reference();
        }
        return $this->acl;
    }

    public function setAcl(ACL $acl): void
    {
        $this->acl = $acl;
    }

    public function getScheduling(): ?Scheduling
    {
        if (!$this->scheduling && $this->scheduling_reference) {
            $reference = $this->scheduling_reference->getClosure();
            $this->scheduling = $reference();
        }
        return $this->scheduling;
    }

    public function setScheduling(Scheduling $scheduling): void
    {
        $this->scheduling = $scheduling;
    }

    public function getWorkflows(): WorkflowInstanceCollection
    {
        return $this->workflows;
    }

    public function setWorkflows(WorkflowInstanceCollection $workflows): void
    {
        $this->workflows = $workflows;
    }

    public function setSchedulingReference(SerializableClosure $scheduling_reference)
    {
        $this->scheduling_reference = $scheduling_reference;
    }

    public function setAclReference(SerializableClosure $acl_reference)
    {
        $this->acl_reference = $acl_reference;
    }

    public function setMetadataReference(SerializableClosure $metadata_reference)
    {
        $this->metadata_reference = $metadata_reference;
    }

    public function getSeriesIdentifier(): string
    {
        return $this->getMetadata()->getField('isPartOf')->getValue();
    }

    public function setSeriesIdentifier(string $series_identifier): void
    {
        $this->getMetadata()->getField('isPartOf')->setValue($series_identifier);
    }

    public function getOwnerUsername(): string
    {
        if ($this->getOwner()) {
            return $this->getOwner()->getNamePresentation();
        } else {
            return $this->getMetadata()->getField('rightsHolder')->getValue() ?: '&nbsp';
        }
    }

    public function getXoctEventAdditions(): xoctEventAdditions
    {
        return $this->xoctEventAdditions;
    }

    public function setXoctEventAdditions(xoctEventAdditions $xoctEventAdditions): void
    {
        $this->xoctEventAdditions = $xoctEventAdditions;
    }

    public function isScheduled(): bool
    {
        return $this->getStatus() === 'EVENTS.EVENTS.STATUS.SCHEDULED';
    }

    public function isLiveEvent(): bool
    {
        return !is_null($this->publications()->getLivePublication());
    }

}