<?php

use Opis\Closure\SerializableClosure;
use srag\DIC\OpenCast\DICTrait;
use srag\Plugins\Opencast\Model\API\ACL\ACL;
use srag\Plugins\Opencast\Model\API\APIObject;
use srag\Plugins\Opencast\Model\API\Metadata\Metadata;
use srag\Plugins\Opencast\Model\API\Scheduling\Processing;
use srag\Plugins\Opencast\Model\API\Scheduling\Scheduling;
use srag\Plugins\Opencast\Model\API\WorkflowInstance\WorkflowInstanceCollection;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsage;
use srag\Plugins\Opencast\Model\Publication\Config\PublicationUsageRepository;
use srag\Plugins\Opencast\Model\Publication\PublicationSelector;
use srag\Plugins\Opencast\Model\WorkflowParameter\Config\WorkflowParameter;
use srag\Plugins\Opencast\Model\WorkflowParameter\Series\SeriesWorkflowParameterRepository;

/**
 * Class xoctEvent
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctEvent extends APIObject {

	use DICTrait;
	const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

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
		xoctEvent::STATE_SUCCEEDED          => 'success',
		xoctEvent::STATE_INSTANTIATED       => 'info',
		xoctEvent::STATE_ENCODING           => 'info',
		xoctEvent::STATE_RECORDING          => 'info',
		xoctEvent::STATE_NOT_PUBLISHED      => 'info',
        xoctEvent::STATE_READY_FOR_CUTTING  => 'info',
		xoctEvent::STATE_SCHEDULED          => 'scheduled',
		xoctEvent::STATE_SCHEDULED_OFFLINE  => 'scheduled',
		xoctEvent::STATE_FAILED             => 'danger',
		xoctEvent::STATE_OFFLINE            => 'info',
		xoctEvent::STATE_LIVE_SCHEDULED     => 'scheduled',
		xoctEvent::STATE_LIVE_RUNNING       => 'info',
		xoctEvent::STATE_LIVE_OFFLINE       => 'info',
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
	public function getArrayForTable() {
		return array(
			'identifier'       => $this->getIdentifier(),
			'title'            => $this->getTitle(),
			'description'      => $this->getDescription(),
			'presenter'        => $this->getPresenter(),
			'location'         => $this->getLocation(),
			'created'          => $this->getCreated()->format(DATE_ATOM),
			'created_unix'     => $this->getCreated()->format('U'),
			'start'            => $this->getStart()->format(DATE_ATOM),
			'start_unix'       => $this->getStart()->format('U'),
			'owner_username'   => $this->getOwnerUsername(),
			'processing_state' => $this->getProcessingState(),
			'object'           => $this,
		);
	}

	/**
	 *
	 */
	public function afterObjectLoad() {

		if (($this->isScheduled() || $this->isLiveEvent()) && (!$this->scheduling || $this->scheduling instanceof stdClass)) {
			$this->loadScheduling();
		}

	}

    public function setAclReference(SerializableClosure $acl_reference)
    {
        $this->acl_reference = $acl_reference;
    }

    public function setMetadataReference(SerializableClosure $metadata_reference)
    {
        $this->metadata_reference = $metadata_reference;
    }


    /**
	 * @param xoctUser $xoctUser
	 *
	 * @return bool
	 * @throws xoctException
	 */
	public function hasWriteAccess(xoctUser $xoctUser) {
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
	public function isOwner(xoctUser $xoctUser) {
		$xoctAcl = $this->getOwnerAcl();
		if (!$xoctAcl instanceof ACLEntry) {
			return false;
		}
		if ($xoctAcl->getRole() == $xoctUser->getOwnerRoleName()) {
			return true;
		}
	}

	/**
	 *
	 */
    public function schedule($rrule = '', $omit_set_owner = false) {
        if (!$omit_set_owner) {
            $this->setOwner(xoctUser::getInstance(self::dic()->user()));
        }

        $data = array();

        $this->setMetadata(new Metadata());
        $this->updateMetadataFromFields(true);
        $this->updateSchedulingFromFields();

//        $this->setCurrentUserAsPublisher();

        if ($rrule) {
            $this->getScheduling()->setRRule($rrule);
        }

        $data['metadata'] = json_encode(array( $this->getMetadata()->__toStdClass() ));
        $data['processing'] = json_encode($this->getProcessing());
        $data['acl'] = json_encode($this->getAcl()->getEntries());
        $data['scheduling'] = json_encode($this->getScheduling()->__toStdClass());

        //		for ($x = 0; $x < 50; $x ++) { // Use this to upload 50 Clips at once, for testing
        $return = json_decode(xoctRequest::root()->events()->post($data));
        //		}

        $this->setIdentifier(is_array($return) ? $return[0]->identifier : $return->identifier);
    }


	/**
	 * @return null|ACLEntry
	 */
	public function getOwnerAcl() {
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
	public function getOwner() {
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
	 * @param xoctUser $xoctUser
	 *
	 * @throws xoctException
	 */
	public function setOwner($xoctUser) {
		$this->getMetadata()->getField('rightsHolder')->setValue($xoctUser->getNamePresentation());

		if (!$xoctUser->getOwnerRoleName()) {
			return;
		}

		$this->removeAllOwnerAcls();
		$acl = new ACLEntry();
		$acl->setAction(ACLEntry::READ);
		$acl->setAllow(true);
		$acl->setRole($xoctUser->getOwnerRoleName());
		$this->getAcl()->add($acl);

		$acl = new ACLEntry();
		$acl->setAction(ACLEntry::WRITE);
		$acl->setAllow(true);
		$acl->setRole($xoctUser->getOwnerRoleName());
		$this->getAcl()->add($acl);
	}


	/**
	 *
	 */
	public function removeOwner() {
		$this->removeAllOwnerAcls();
		$this->getMetadata()->getField('rightsHolder')->setValue('');
	}


	/**
	 *
	 */
	public function removeAllOwnerAcls() {
		$standard_roles = xoctConf::getConfig(xoctConf::F_STD_ROLES);
        $ACLEntries = $this->getAcl()->getEntries();
        foreach ($ACLEntries as $i => $acl) {
			if ((strpos($acl->getRole(), str_replace('{IDENTIFIER}', '', xoctUser::getOwnerRolePrefix())) !== false)
				&& !in_array($acl->getRole(), $standard_roles)) {
				unset($ACLEntries[$i]);
			}
		}
		$this->acl->setEntries($ACLEntries);
	}

    /**
     * @return bool
     * @throws xoctException
     */
    public function unpublish() {
        $workflow = xoctConf::getConfig(xoctConf::F_WORKFLOW_UNPUBLISH);
        xoctRequest::root()->workflows()->post(array(
            'workflow_definition_identifier' => $workflow,
            'event_identifier' => $this->getIdentifier()
        ));
        self::removeFromCache($this->getIdentifier());
        return true;
	}


	/**
	 *
	 */
	public function loadScheduling() {
		if ($this->getIdentifier()) {
		    if ($this->scheduling instanceof stdClass) {
		        $this->scheduling = new Scheduling($this->getIdentifier(), $this->scheduling);
            } else {
                $this->scheduling = new Scheduling($this->getIdentifier());
            }
			$this->setStart($this->scheduling->getStart());
			$this->setEnd($this->scheduling->getEnd());
			$this->setLocation($this->scheduling->getAgentId());
		} else {
			$this->scheduling = new Scheduling();
		}
	}


    /**
     *
     */
    public function loadWorkflows() {
	    if ($this->getIdentifier()) {
	        $this->workflows = new WorkflowInstanceCollection($this->getIdentifier());
        } else {
	        $this->workflows = new WorkflowInstanceCollection();
        }
    }

	/**
	 * @var bool
	 */
	protected $processing_state_init = false;


	/**
	 *
	 */
	protected function initProcessingState() {
		if (!$this->getIdentifier()) {
			return 0;
		}
		if ($this->processing_state_init) {
			return true;
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
					if (!in_array($publication_player->getChannel(),$this->publication_status))
					{
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


	/**
	 * @param bool $as_admin
	 */
	public function addDefaultWorkflowParameters($as_admin = true) {
		/** @var WorkflowParameter $xoctWorkflowParameter */
		foreach (WorkflowParameter::get() as $xoctWorkflowParameter) {
			$default_value = $as_admin ? $xoctWorkflowParameter->getDefaultValueAdmin() : $xoctWorkflowParameter->getDefaultValueMember();

			switch ($default_value) {
				case WorkflowParameter::VALUE_ALWAYS_ACTIVE:
					$this->workflow_parameters[$xoctWorkflowParameter->getId()] = 1;
					break;
				case WorkflowParameter::VALUE_ALWAYS_INACTIVE:
					$this->workflow_parameters[$xoctWorkflowParameter->getId()] = 0;
					break;
				default:
					break;
			}
		}
	}

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
	 * @var Scheduling
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
	 * @var array
	 */
	protected $workflow_parameters = [];


	public function getStart() {
		return $this->getMetadata()->getField('startDate')->getValue();
	}


    public function setStart($start) {
        $this->getMetadata()->getField('startDate')->setValue($start);
    }


    /**
	 * this should only be called on scheduled events
	 *
	 * @return DateTime
	 */
	public function getEnd() {
        return $this->getMetadata()->getField('end')->getValue();
	}


    /**
     * @param $end
     * @throws ilTimeZoneException
     */
    public function setEnd($end) {
        $this->getMetadata()->getField('end')->setValue($end);
    }


    /**
     * @return string
     */
    public function getSeries() : string
    {
        return $this->series;
    }


    /**
     * @param string $series
     */
    public function setSeries(string $series)
    {
        $this->series = $series;
    }

	/**
	 * @return string
	 */
	public function getIdentifier() {
		return $this->getMetadata()->getField('identifier')->getValue();
	}


	/**
	 * @param string $identifier
	 */
	public function setIdentifier($identifier) {
		$this->getMetadata()->getField('identifier')->setValue($identifier);
	}


	/**
	 * @return DateTime
	 */
	public function getCreated()
    {
		return $this->getMetadata()->getField('created')->getValue();
	}


	/**
	 * @param DateTime $created
	 */
	public function setCreated(DateTime $created)
    {
		$this->getMetadata()->getField('created')->setValue($created);
	}


	/**
	 * @return string
	 */
	public function getCreator()
    {
		return $this->getMetadata()->getField('creator')->getValue();
	}


	/**
	 * @param string $creator
	 */
	public function setCreator(string $creator)
    {
		$this->getMetadata()->getField('creator')->setValue($creator);
	}


	/**
	 * @return array
	 */
	public function getContributors() {
        return $this->getMetadata()->getField('contributors')->getValue();
	}


	/**
	 * @param array $contributors
	 */
	public function setContributors(array $contributors) {
        $this->getMetadata()->getField('contributors')->setValue($contributors);
	}


	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->getMetadata()->getField('description')->getValue();
	}


	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->getMetadata()->getField('description')->setValue($description);
	}


	/**
	 * @return string
	 */
	public function getDuration()
    {
		return $this->getMetadata()->getField('duration')->getValue();
	}


    /**
     * @param string $duration
     */
    public function setDuration(string $duration)
    {
        $this->getMetadata()->getField('description')->setValue($duration);
    }


    /**
	 *
	 */
	public function getDurationArrayForInput() {
		if (!$this->getDuration()) {
			return 0;
		}
		$seconds = floor($this->getDuration() / 1000);
		$minutes = floor($seconds / 60);
		$hours = floor($minutes / 60);

		return array (
			'hh' => $hours,
			'mm' => $minutes % 60,
			'ss' => $seconds % 60
		);
	}


	/**
	 * @return boolean
	 */
	public function hasPreviews() {
		return $this->has_previews;
	}


	/**
	 * @param boolean $has_previews
	 */
	public function setHasPreviews($has_previews) {
		$this->has_previews = $has_previews;
	}


	/**
	 * @return string
	 */
	public function getLocation() {
		return $this->getMetadata()->getField('location')->getValue();
	}


	/**
	 * @param string $location
	 */
	public function setLocation($location) {
		$this->getMetadata()->getField('location')->setValue($location);
	}


	/**
	 * @return String
	 */
	public function getPresenter() {
		return $this->getMetadata()->getField('creator')->getValue();
	}


	/**
	 * @param String $presenter
	 */
	public function setPresenter($presenter) {
		$this->getMetadata()->getField('creator')->setValue([$presenter]);
	}

	/**
	 * @return array
	 */
	public function getPublicationStatus() {
		return $this->publication_status;
	}


	/**
	 * @param array $publication_status
	 */
	public function setPublicationStatus($publication_status) {
		$this->publication_status = $publication_status;
	}


	/**
	 * @return String
	 */
	public function getProcessingState() {
		$this->initProcessingState();

		return $this->processing_state;
	}


	/**
	 * @param String $processing_state
	 */
	public function setProcessingState($processing_state) {
		$this->processing_state = $processing_state;
	}



	/**
	 * @return array
	 */
	public function getSubjects() {
		return $this->getMetadata()->getField('subjects')->getValue();
	}


	/**
	 * @param array $subjects
	 */
	public function setSubjects($subjects) {
		$this->getMetadata()->getField('subjects')->setValue($subjects);
	}


	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->getMetadata()->getField('title')->getValue();
	}


	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->getMetadata()->getField('title')->setValue($title);
	}


	/**
	 * @return Metadata
	 */
	public function getMetadata() {
		if (!$this->metadata) {
            $reference = $this->metadata_reference->getClosure();
			$this->metadata = $reference();
		}
		return $this->metadata;
	}


	/**
	 * @param Metadata $metadata
	 */
	public function setMetadata(Metadata $metadata) {
		$this->metadata = $metadata;
	}


	/**
	 * @return ACL
	 */
	public function getAcl() : ACL
    {
        if (!$this->acl) {
            $reference = $this->acl_reference->getClosure();
            $this->acl = $reference();
        }
		return $this->acl;
	}


	/**
	 * @param ACL $acl
	 */
	public function setAcl(ACL $acl) {
		$this->acl = $acl;
	}


	/**
	 * @return Scheduling
	 */
	public function getScheduling() {
		if (!$this->scheduling) {
			$this->loadScheduling();
		}
		return $this->scheduling;
	}


	/**
	 * @param Scheduling $scheduling
	 */
	public function setScheduling($scheduling) {
		$this->scheduling = $scheduling;
	}


    /**
     * @return WorkflowInstanceCollection
     */
    public function getWorkflows() : WorkflowInstanceCollection
    {
        return $this->workflows;
    }


    /**
     * @param WorkflowInstanceCollection $workflows
     */
    public function setWorkflows(WorkflowInstanceCollection $workflows)
    {
        $this->workflows = $workflows;
    }


	/**
	 * @return string
	 */
	public function getSeriesIdentifier() {
		return $this->getMetadata()->getField('isPartOf')->getValue();
	}


	/**
	 * @param string $series_identifier
	 */
	public function setSeriesIdentifier(string $series_identifier) {
		$this->getMetadata()->getField('isPartOf')->setValue($series_identifier);
	}


	/**
	 * @return string
	 */
	public function getOwnerUsername() {
		if ($this->getOwner()) {
			return $this->getOwner()->getNamePresentation();
		} else {
			return $this->getMetadata()->getField('rightsHolder')->getValue() ?: '&nbsp';
		}
	}


	/**
	 * @return string
	 */
	public function getSource()
    {
		return $this->getMetadata()->getField('source')->getValue();
	}


	/**
	 * @param string $source
	 */
	public function setSource(string $source)
    {
		$this->getMetadata()->getField('source')->setValue($source);
	}

	/**
	 *
	 */
	protected function updateSchedulingFromFields() {
	    $this->getScheduling()->setDuration($this->getDuration());
		$this->getScheduling()->setEnd($this->getEnd());
		$this->getScheduling()->setStart($this->getStart());
		$this->getScheduling()->setAgentId($this->getLocation());
	}


	/**
	 * @return Processing
	 */
	public function getProcessing() {
		$workflow = xoctConf::getConfig(xoctConf::F_WORKFLOW);
		$configuration = new stdClass();
		foreach ($this->workflow_parameters as $workflow_parameter => $value) {
			$configuration->$workflow_parameter = ($value ? 'true' : 'false');
		}

		return new Processing($workflow, $configuration);
	}

	/**
	 * @return xoctEventAdditions
	 */
	public function getXoctEventAdditions() {
		return $this->xoctEventAdditions;
	}


	/**
	 * @param xoctEventAdditions $xoctEventAdditions
	 */
	public function setXoctEventAdditions(xoctEventAdditions $xoctEventAdditions) {
		$this->xoctEventAdditions = $xoctEventAdditions;
	}


	/**
	 * @return bool
	 */
	public function isScheduled() {
		return in_array($this->getProcessingState(), [
		    self::STATE_SCHEDULED,
            self::STATE_SCHEDULED_OFFLINE,
            self::STATE_RECORDING
        ]);
	}

	/**
	 * @return bool
	 */
	public function isLiveEvent() {
		return !is_null($this->publications()->getLivePublication());
	}

    /**
     * @return PublicationSelector
     */
    public function publications() : PublicationSelector
    {
        if (!$this->publications) {
            $this->publications = new PublicationSelector($this);
        }
        return $this->publications;
    }


}