<?php

namespace srag\Plugins\Opencast\Model\Event;

use DateTimeImmutable;
use srag\Plugins\Opencast\Model\ACL\ACL;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Metadata\MetadataField;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository;
use srag\Plugins\Opencast\Model\Publication\PublicationSelector;
use srag\Plugins\Opencast\Model\Scheduling\Scheduling;
use srag\Plugins\Opencast\Model\WorkflowInstance\WorkflowInstanceCollection;

/**
 * Opencast Event Object
 */
class Event
{
    public const STATE_SUCCEEDED = 'SUCCEEDED';
    public const STATE_OFFLINE = 'OFFLINE';
    public const STATE_SCHEDULED = 'SCHEDULED';
    public const STATE_SCHEDULED_OFFLINE = 'SCHEDULED_OFFLINE';
    public const STATE_INSTANTIATED = 'INSTANTIATED';
    public const STATE_ENCODING = 'RUNNING';
    public const STATE_RECORDING = 'RECORDING';
    public const STATE_NOT_PUBLISHED = 'NOT_PUBLISHED';
    public const STATE_READY_FOR_CUTTING = 'READY_FOR_CUTTING';
    public const STATE_FAILED = 'FAILED';
    public const STATE_LIVE_SCHEDULED = 'LIVE_SCHEDULED';
    public const STATE_LIVE_RUNNING = 'LIVE_RUNNING';
    public const STATE_LIVE_OFFLINE = 'LIVE_OFFLINE';

    /**
     * @var array
     *
     * used for colouring
     */
    public static $state_mapping = [
        Event::STATE_SUCCEEDED => 'success',
        Event::STATE_INSTANTIATED => 'info',
        Event::STATE_ENCODING => 'info',
        Event::STATE_RECORDING => 'info',
        Event::STATE_NOT_PUBLISHED => 'info',
        Event::STATE_READY_FOR_CUTTING => 'info',
        Event::STATE_SCHEDULED => 'scheduled',
        Event::STATE_SCHEDULED_OFFLINE => 'scheduled',
        Event::STATE_FAILED => 'danger',
        Event::STATE_OFFLINE => 'info',
        Event::STATE_LIVE_SCHEDULED => 'scheduled',
        Event::STATE_LIVE_RUNNING => 'info',
        Event::STATE_LIVE_OFFLINE => 'info',
    ];
    /**
     * @var PublicationSelector
     */
    protected $publications;
    /**
     * @var EventAdditionsAR
     */
    protected $xoctEventAdditions;
    /**
     * @var string
     */
    private $status;

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
     * @var bool
     */
    protected $processing_state_init = false;

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getArrayForTable(): array
    {
        $array = array_column(
            array_map(function (MetadataField $mf): array {
                return [$mf->getId(), $mf->toString()];
            }, $this->getMetadata()->getFields()),
            1,
            0
        );
        $sortable = array_column(
            array_map(function (MetadataField $mf): array {
                return [$mf->getId() . '_s', $mf->getValueFormatted()];
            }, $this->getMetadata()->getFields()),
            1,
            0
        );
        $array['object'] = $this;
        return $array + $sortable;
    }

    /**
     *
     */
    public function loadWorkflows(): void
    {
        if ($this->getIdentifier() !== '' && $this->getIdentifier() !== '0') {
            $this->workflows = new WorkflowInstanceCollection($this->getIdentifier());
        } else {
            $this->workflows = new WorkflowInstanceCollection();
        }
    }

    protected function initProcessingState(): void
    {
        // todo: think this over
        if ($this->getIdentifier() === '' || $this->getIdentifier() === '0') {
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
                    $this->setProcessingState(
                        $this->isLiveEvent() ? self::STATE_LIVE_OFFLINE : self::STATE_SCHEDULED_OFFLINE
                    );
                } else {
                    $this->setProcessingState(
                        $this->isLiveEvent() ? self::STATE_LIVE_SCHEDULED : self::STATE_SCHEDULED
                    );
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
        return $this->metadata;
    }

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
        return $this->acl;
    }

    public function setAcl(ACL $acl): void
    {
        $this->acl = $acl;
    }

    public function getScheduling(): ?Scheduling
    {
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

    public function getSeriesIdentifier(): string
    {
        return $this->getMetadata()->getField('isPartOf')->getValue();
    }

    public function setSeriesIdentifier(string $series_identifier): void
    {
        $this->getMetadata()->getField('isPartOf')->setValue($series_identifier);
    }

    public function getXoctEventAdditions(): EventAdditionsAR
    {
        return $this->xoctEventAdditions;
    }

    public function setXoctEventAdditions(EventAdditionsAR $xoctEventAdditions): void
    {
        $this->xoctEventAdditions = $xoctEventAdditions;
    }

    public function isScheduled(): bool
    {
        return in_array($this->getStatus(), [
            'EVENTS.EVENTS.STATUS.SCHEDULED',
            "EVENTS.EVENTS.STATUS.RECORDING"
        ]);
    }

    public function isLiveEvent(): bool
    {
        return !is_null($this->publications()->getLivePublication());
    }
}
