<?php

use srag\DIC\OpenCast\DICTrait;
use srag\Plugins\Opencast\Model\API\APIObject;
use srag\Plugins\Opencast\Model\API\Event\EventRepository;
use srag\Plugins\Opencast\Model\API\Scheduling\Scheduling;
use srag\Plugins\Opencast\Model\API\WorkflowInstance\WorkflowInstanceCollection;
use srag\Plugins\Opencast\Model\Config\PublicationUsage\PublicationSelector;
use srag\Plugins\Opencast\Model\Config\PublicationUsage\PublicationUsage;
use srag\Plugins\Opencast\Model\Config\PublicationUsage\PublicationUsageRepository;

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

	const PRESENTER_SEP = ';';
	const TZ_EUROPE_ZURICH = 'Europe/Zurich';
	const TZ_UTC = 'UTC';

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
     * @var PublicationUsageRepository
     */
    protected $publication_usage_repository;

    /**
	 * @param $identifier
	 *
	 * @return xoctEvent
	 */
	public static function find(string $identifier) {
		/**
		 * @var $xoctEvent xoctEvent
		 */
		$xoctEvent = parent::find($identifier);
		$xoctEvent->afterObjectLoad();

		return $xoctEvent;
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
	 * @param string $identifier
	 */
	public function __construct($identifier = '') {
	    $this->publication_usage_repository = new PublicationUsageRepository();
		if ($identifier) {
			$this->setIdentifier($identifier);
			$this->read();
		}
	}


	/**
	 *
	 */
	public function read() {
		if (!$this->isLoaded()) {
			$data = json_decode(xoctRequest::root()->events($this->getIdentifier())->get());
			$this->loadFromStdClass($data);
		}
		$this->afterObjectLoad();
	}


	/**
	 *
	 */
	public function afterObjectLoad() {
		if (!$this->getAcl()) {
			$this->loadAcl();
		}

		$this->initProcessingState();

		if (($this->isScheduled() || $this->isLiveEvent()) && (!$this->scheduling || $this->scheduling instanceof stdClass)) {
			$this->loadScheduling();
		}

		// if ($this->isScheduled() && !$this->workflows) {
		//     $this->loadWorkflows();
        // }

		if (!$this->getXoctEventAdditions()) {
			$this->initAdditions();
		}

        if (!$this->metadata) {
            $this->loadMetadata();
        }

        // if no_metadata option is set, the metadata below will already be initialized
        if (EventRepository::$no_metadata) {
            return;
        }

        if (!$this->getSeriesIdentifier()) {
			$this->setSeriesIdentifier($this->getMetadata()->getField('isPartOf')->getValue());
		}

		if ($this->getOwner()) {
			$this->setOwnerUsername($this->getOwner()->getNamePresentation());
		} elseif ($owner = $this->getMetadata()->getField('rightsHolder')->getValue()) {
			$this->setOwnerUsername($owner);
		}

		$this->setSource($this->getMetadata()->getField('source')->getValue());
	}


	/**
	 * sets workflow parameters while adding the parameters with status "set automatically" automatically
	 *
	 * @param      $parameters array Workflow parameters to be set. Note that the parameters with value "set automatically" will be set automatically,
	 *                         so it suffices to pass the additional ones
	 * @param      $obj_id
	 * @param bool $as_admin
	 */
	public function setWorkflowParametersForObjId(array $parameters, int $obj_id, bool $as_admin = true) {
		$parameters_in_form = xoctSeriesWorkflowParameterRepository::getInstance()->getParametersInFormForObjId($obj_id, $as_admin);
		$not_set_in_form = array_diff(array_keys($parameters_in_form), array_keys($parameters));
		foreach ($not_set_in_form as $id) {
			$parameters[$id] = 0;
		}
		$automatically_set = xoctSeriesWorkflowParameterRepository::getInstance()->getAutomaticallySetParametersForObjId($obj_id, $as_admin);
		$this->setWorkflowParameters(array_merge($automatically_set, $parameters));
	}


    /**
     * @param array $parameters
     */
    public function setGeneralWorkflowParameters(array $parameters)
    {
        $parameters_in_form = xoctSeriesWorkflowParameterRepository::getInstance()->getGeneralParametersInForm();
        $not_set_in_form = array_diff(array_keys($parameters_in_form), array_keys($parameters));
        foreach ($not_set_in_form as $id) {
            $parameters[$id] = 0;
        }
        $automatically_set = xoctSeriesWorkflowParameterRepository::getInstance()->getGeneralAutomaticallySetParameters();
        $this->setWorkflowParameters(array_merge($automatically_set, $parameters));
    }


    /**
	 * @param $key
	 *
	 * @return string
	 */
	protected function mapKey($key) {
		switch ($key) {
			case 'is_part_of':
				return 'series_identifier';
			case 'rightsHolder':
			case 'rights':
				return 'owner_username';
			default:
				return $key;
		}
	}

    /**
     * @param $fieldname
     * @param $value
     *
     * @return array|DateTime|mixed|string|Metadata
     * @throws xoctException
     */
	protected function wakeup($fieldname, $value) {
		switch ($fieldname) {
			case 'created':
			case 'start_time':
				return $this->getDefaultDateTimeObject($value);
			case 'metadata':
				$metadata = new Metadata();
				$metadata->loadFromArray($value);

				return $metadata;
			case 'acl':
				$acls = array();
				foreach ($value as $acl_array) {
					$acl = new xoctAcl();
					$acl->loadFromStdClass($acl_array);
					$acls[] = $acl;
				}

				return $acls;
			case 'publications':
			    $publications = new PublicationSelector($this);
			    $publications->loadFromArray($value);
				return $publications;
			case 'presenter':
				return is_array($value) ? implode(self::PRESENTER_SEP, $value) : $value;
			default:
				return $value;
		}
	}


    /**
     * @param $fieldname
     * @param $value
     *
     * @return array|DateTime|int|Metadata|mixed|xoctAcl[]|xoctPublication[]
     * @throws ReflectionException
     */
    protected function sleep($fieldname, $value)
    {
        switch ($fieldname) {
            case 'created':
            case 'start_time':
                /** @var $value DateTime */
                return $value instanceof DateTime ? $value->getTimestamp() : 0;
                break;
            case 'metadata':
                /** @var $value Metadata */
                return $value->__toArray();
            case 'acl':
                /** @var $value xoctAcl[] */
                $acls = array();
                foreach ($value as $acl) {
                    $acls[] = $acl->__toArray();
                }

                return $acls;
            default:
                return $value;
        }
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
		if (!$xoctAcl instanceof xoctAcl) {
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

        $this->setMetadata(Metadata::getSet(Metadata::FLAVOR_DUBLINCORE_EPISODES));
        $this->updateMetadataFromFields(true);
        $this->updateSchedulingFromFields();

//        $this->setCurrentUserAsPublisher();

        if ($rrule) {
            $this->getScheduling()->setRRule($rrule);
        }

        $data['metadata'] = json_encode(array( $this->getMetadata()->__toStdClass() ));
        $data['processing'] = json_encode($this->getProcessing());
        $data['acl'] = json_encode($this->getAcl());
        $data['scheduling'] = json_encode($this->getScheduling()->__toStdClass());

        //		for ($x = 0; $x < 50; $x ++) { // Use this to upload 50 Clips at once, for testing
        $return = json_decode(xoctRequest::root()->events()->post($data));
        //		}

        $this->setIdentifier(is_array($return) ? $return[0]->identifier : $return->identifier);
    }


    /**
	 *
	 */
	public function update() {
		// Metadata
		$this->updateMetadataFromFields($this->isScheduled());
		$this->getMetadata()->setFlavor(Metadata::FLAVOR_DUBLINCORE_EPISODES);
		$this->getMetadata()->removeField('identifier');
		$this->getMetadata()->removeField('isPartOf');
		$this->getMetadata()->removeField('createdBy'); // can't be updated at the moment

		$data['metadata'] = json_encode(array( $this->getMetadata()->__toStdClass() ));

		if ($this->isScheduled()) {
			$this->updateSchedulingFromFields();
			if ($this->getScheduling()->hasChanged()) {
                $data['scheduling'] = json_encode( $this->getScheduling()->__toStdClass());
            }
            $data['processing'] = json_encode($this->getProcessing());
		}

		// All Data
		xoctRequest::root()->events($this->getIdentifier())->post($data);
		$this->updateAcls();
		self::removeFromCache($this->getIdentifier());
	}


	/**
	 *
	 */
	public function updateAcls() {
		$xoctAclStandardSets = new xoctAclStandardSets();
		foreach ($xoctAclStandardSets->getAcls() as $acl) {
			$this->addAcl($acl);
		}

		xoctRequest::root()->events($this->getIdentifier())->acl()->put(array('acl' => json_encode($this->getAcl()) ));
		self::removeFromCache($this->getIdentifier());
	}


	/**
	 *
	 */
	public function updateSeries() {
		$this->updateMetadataFromFields(false);
		$this->getMetadata()->getField('isPartOf')->setValue($this->getSeriesIdentifier());
		$data['metadata'] = json_encode(array( $this->getMetadata()->__toStdClass() ));
		xoctRequest::root()->events($this->getIdentifier())->post($data);
		self::removeFromCache($this->getIdentifier());
	}


	/**
	 * @return null|xoctAcl
	 */
	public function getOwnerAcl() {
		static $owner_acl;
		if (isset($owner_acl[$this->getIdentifier()])) {
			return $owner_acl[$this->getIdentifier()];
		}
		foreach ($this->getAcl() as $acl) {
			if (strpos($acl->getRole(), str_replace('{IDENTIFIER}', '', xoctUser::getOwnerRolePrefix())) !== false) {
				$owner_acl[$this->getIdentifier()] = $acl;

				return $acl;
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
		if ($acl instanceof xoctAcl) {
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
		$acl = new xoctAcl();
		$acl->setAction(xoctAcl::READ);
		$acl->setAllow(true);
		$acl->setRole($xoctUser->getOwnerRoleName());
		$this->addAcl($acl);

		$acl = new xoctAcl();
		$acl->setAction(xoctAcl::WRITE);
		$acl->setAllow(true);
		$acl->setRole($xoctUser->getOwnerRoleName());
		$this->addAcl($acl);
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
		foreach ($this->getAcl() as $i => $acl) {
			if ((strpos($acl->getRole(), str_replace('{IDENTIFIER}', '', xoctUser::getOwnerRolePrefix())) !== false)
				&& !in_array($acl->getRole(), $standard_roles)) {
				unset($this->acl[$i]);
			}
		}
		sort($this->acl);
	}

    /**
     * @return bool
     * @throws xoctException
     */
	public function delete() {
		xoctRequest::root()->events($this->getIdentifier())->delete();
		foreach (xoctInvitation::where(array('event_identifier' => $this->getIdentifier()))->get() as $invitation) {
			$invitation->delete();
		}
		return true;
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
	protected function loadAcl() {
		$data = json_decode(xoctRequest::root()->events($this->getIdentifier())->acl()->get());
		$acls = array();
		foreach ($data as $d) {
			$p = new xoctAcl();
			$p->loadFromStdClass($d);
			$acls[] = $p;
		}
		$this->setAcl($acls);
	}


	/**
	 *
	 */
	public function loadMetadata() {
		if ($this->getIdentifier() && !EventRepository::$no_metadata) {
			$data = json_decode(xoctRequest::root()->events($this->getIdentifier())->metadata()->get());
			if (is_array($data)) {
				foreach ($data as $d) {
					if ($d->flavor == Metadata::FLAVOR_DUBLINCORE_EPISODES) {
						$xoctMetadata = new Metadata();
						$xoctMetadata->loadFromStdClass($d);
						$this->setMetadata($xoctMetadata);
					}
				}
			}
		}
		if (!$this->metadata) {
			$this->setMetadata(Metadata::getSet(Metadata::FLAVOR_DUBLINCORE_EPISODES));
		}
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
		/** @var xoctWorkflowParameter $xoctWorkflowParameter */
		foreach (xoctWorkflowParameter::get() as $xoctWorkflowParameter) {
			$default_value = $as_admin ? $xoctWorkflowParameter->getDefaultValueAdmin() : $xoctWorkflowParameter->getDefaultValueMember();

			switch ($default_value) {
				case xoctWorkflowParameter::VALUE_ALWAYS_ACTIVE:
					$this->workflow_parameters[$xoctWorkflowParameter->getId()] = 1;
					break;
				case xoctWorkflowParameter::VALUE_ALWAYS_INACTIVE:
					$this->workflow_parameters[$xoctWorkflowParameter->getId()] = 0;
					break;
				default:
					break;
			}
		}
	}

	/**
	 * @var string
	 */
	protected $identifier = '';
	/**
	 * @var int
	 */
	protected $archive_version;
	/**
	 * @var DateTime
	 */
	protected $created;
	/**
	 * @var string
	 */
	protected $creator;
	/**
	 * @var array
	 */
	protected $contributors;
	/**
	 * @var string
	 */
	protected $description;
	/**
	 * @var int
	 */
	protected $duration;
	/**
	 * @var bool
	 */
	protected $has_previews;
	/**
	 * @var string
	 */
	protected $location;
	/**
	 * @var string
	 */
	protected $presenter;
	/**
	 * @var array
	 */
	protected $publication_status;
	/**
	 * @var String
	 */
	protected $processing_state;
	/**
	 * @var DateTime
	 */
	protected $start_time;
	/**
	 * @var DateTime
	 */
	protected $start;
	/**
	 * @var DateTime
	 */
	protected $end;
	/**
	 * @var array
	 */
	protected $subjects;
	/**
	 * @var string
	 */
	protected $title;
	/**
	 * @var Metadata
	 */
	protected $metadata = null;
	/**
	 * @var xoctAcl[]
	 */
	protected $acl = array();
	/**
	 * @var Scheduling
	 */
	protected $scheduling = null;
    /**
     * @var WorkflowInstanceCollection
     */
	protected $workflows;
	/**
	 * @var string
	 */
	protected $series_identifier = '';
    /**
     * @var string
     */
	protected $series;
	/**
	 * @var string
	 */
	protected $owner_username = '';
	/**
	 * @var string
	 */
	protected $source = '';
	/**
	 * @var array
	 */
	protected $workflow_parameters = [];


	/**
	 * @return DateTime
	 */
	public function getStart() {
		return ($this->start instanceof DateTime) ? $this->start : $this->getDefaultDateTimeObject($this->start);
	}


	/**
	 * this should only be called on scheduled events
	 *
	 * @return DateTime
	 */
	public function getEnd() {
		return $this->end;
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
     * @param $end
     * @throws ilTimeZoneException
     */
	public function setEnd($end) {
        $date_time_zone = new DateTimeZone(ilTimeZone::_getInstance()->getIdentifier());
        if ($end instanceof DateTime) {
            $end->setTimezone($date_time_zone);
            $this->end = $end;
        } else {
            $this->end = new DateTime($end, $date_time_zone);;
        }
	}


    /**
     * @param $start
     * @throws ilTimeZoneException
     */
	public function setStart($start) {
	    $date_time_zone = new DateTimeZone(ilTimeZone::_getInstance()->getIdentifier());
        if ($start instanceof DateTime) {
            $start->setTimezone($date_time_zone);
            $this->start = $start;
        } else {
            $this->start = new DateTime($start, $date_time_zone);
        }
	}

	/**
	 * @return string
	 */
	public function getIdentifier() {
		return $this->identifier;
	}


	/**
	 * @param string $identifier
	 */
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}


	/**
	 * @return int
	 */
	public function getArchiveVersion() {
		return $this->archive_version;
	}


	/**
	 * @param int $archive_version
	 */
	public function setArchiveVersion($archive_version) {
		$this->archive_version = $archive_version;
	}


	/**
	 * @return DateTime
	 */
	public function getCreated() {
		return ($this->created instanceof DateTime) ? $this->created : $this->getDefaultDateTimeObject($this->created);
	}


	/**
	 * @param DateTime $created
	 */
	public function setCreated($created) {
		$this->created = $created;
	}


	/**
	 * @return string
	 */
	public function getCreator() {
		return $this->creator;
	}


	/**
	 * @param string $creator
	 */
	public function setCreator($creator) {
		$this->creator = $creator;
	}


	/**
	 * @return array
	 */
	public function getContributors() {
		return $this->contributors;
	}


	/**
	 * @param array $contributors
	 */
	public function setContributors($contributors) {
		$this->contributors = $contributors;
	}


	/**
	 * @return string
	 */
	public function getDescription() {
		return $this->description;
	}


	/**
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}


	/**
	 * @return int
	 */
	public function getDuration() {
		return $this->duration;
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
	 * @param int $duration
	 */
	public function setDuration($duration) {
		$this->duration = $duration;
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
		return $this->location;
	}


	/**
	 * @param string $location
	 */
	public function setLocation($location) {
		$this->location = $location;
	}


	/**
	 * @return String
	 */
	public function getPresenter() {
		return $this->presenter;
	}


	/**
	 * @param String $presenter
	 */
	public function setPresenter($presenter) {
		$this->presenter = $presenter;
	}

	public function setPresenters($presenter) {
		$this->setPresenter($presenter);
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
	 * @return DateTime
	 */
	public function getStartTime() {
		return $this->start_time;
	}


	/**
	 * @param DateTime $start_time
	 */
	public function setStartTime($start_time) {
		$this->start_time = $start_time;
	}


	/**
	 * @return array
	 */
	public function getSubjects() {
		return $this->subjects;
	}


	/**
	 * @param array $subjects
	 */
	public function setSubjects($subjects) {
		$this->subjects = $subjects;
	}


	/**
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}


	/**
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}


	/**
	 * @return Metadata
	 */
	public function getMetadata() {
		if (!$this->metadata) {
			$this->loadMetadata();
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
	 * @return xoctAcl[]
	 */
	public function getAcl() {
		return array_values($this->acl);
	}


	/**
	 * @param xoctAcl[] $acl
	 */
	public function setAcl($acl) {
		$this->acl = $acl;
	}


	/**
	 * @param xoctAcl $acl
	 *
	 * @return bool
	 */
	public function addAcl(xoctAcl $acl) {
		foreach ($this->getAcl() as $existingAcl) {
			if ($acl->getRole() == $existingAcl->getRole() && $acl->getAction() == $existingAcl->getAction()) {
				return false;
			}
		}

		$this->acl[] = $acl;

		return true;
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
		return $this->series_identifier;
	}


	/**
	 * @param string $series_identifier
	 */
	public function setSeriesIdentifier($series_identifier) {
		$this->series_identifier = $series_identifier;
	}


	/**
	 * @return string
	 */
	public function getOwnerUsername() {
		if ($this->owner_username) {
			return $this->owner_username;
		} elseif ($this->getOwner()) {
			return $this->getOwner()->getNamePresentation();
		} else {
			return '&nbsp';
		}
	}


	/**
	 * @param string $owner_username
	 */
	public function setOwnerUsername($owner_username) {
		$this->owner_username = $owner_username;
	}


	/**
	 * @return string
	 */
	public function getSource() {
		return $this->source;
	}


	/**
	 * @param string $source
	 */
	public function setSource($source) {
		$this->source = $source;
	}


	/**
	 * @return array
	 */
	public function getWorkflowParameters() {
		return $this->workflow_parameters;
	}


	/**
	 * @param array $workflow_parameters
	 */
	public function setWorkflowParameters($workflow_parameters) {
		$this->workflow_parameters = $workflow_parameters;
	}

	public function setWorkflowParameter(string $parameter_id, $value)
    {
        $this->workflow_parameters[$parameter_id] = $value;
    }


	/**
	 *
	 */
	public function updateMetadataFromFields($scheduled) {
		$title = $this->getMetadata()->getField('title');
		$title->setValue($this->getTitle());

		$description = $this->getMetadata()->getField('description');
		$description->setValue($this->getDescription());


		$subjects = $this->getMetadata()->getField('subjects');
		$subjects->setValue(array());

		$is_part_of = $this->getMetadata()->getField('isPartOf');
		$is_part_of->setValue($this->getSeriesIdentifier());


		$source = $this->getMetadata()->getField('source');
		$source->setValue($this->getSource());

		$presenter = $this->getMetadata()->getField('creator');
		$presenter->setValue(explode(self::PRESENTER_SEP, $this->getPresenter()));

//		if (!$scheduled) {
            $location = $this->getMetadata()->getField('location');
            $location->setValue($this->getLocation());

            $start = $this->getStart()->setTimezone(new DateTimeZone(self::TZ_UTC));

            $startDate = $this->getMetadata()->getField('startDate');
            $startDate->setValue($start->format('Y-m-d'));

            $startTime = $this->getMetadata()->getField('startTime');
            $startTime->setValue($start->format('H:i:s.v\Z'));
//        }
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
	 * @param null $input
	 * @return DateTime
	 */
	public function getDefaultDateTimeObject($input = null) {
		if ($input instanceof DateTime) {
			$input = $input->format(DATE_ATOM);
		}
		if (!$input) {
			$input = 'now';
		}
		try {
			$timezone = new DateTimeZone(self::dic()->iliasIni()->readVariable('server', 'timezone'));
		} catch (Exception $e) {
			$timezone = null;
		}

		$datetime = is_int($input) ? new DateTime(date('Y-m-d H:i:s', $input)) : new DateTime($input);
		$datetime->setTimezone($timezone);
		return $datetime;
	}




	/**
	 * @return stdClass
	 */
	public function getProcessing() {
		$processing = new stdClass();
		$processing->workflow = xoctConf::getConfig(xoctConf::F_WORKFLOW);
		$processing->configuration = new stdClass();
		foreach ($this->workflow_parameters as $workflow_parameter => $value) {
			$processing->configuration->$workflow_parameter = ($value ? 'true' : 'false');
		}

		return $processing;
	}


	/**
	 * @return xoctEventAdditions
	 */
	public function getXoctEventAdditions() {
		$this->initAdditions();

		return $this->xoctEventAdditions;
	}


	/**
	 * @param xoctEventAdditions $xoctEventAdditions
	 */
	public function setXoctEventAdditions(xoctEventAdditions $xoctEventAdditions) {
		$this->xoctEventAdditions = $xoctEventAdditions;
	}


	/**
	 *
	 */
	protected function initAdditions() {
		if ($this->xoctEventAdditions instanceof xoctEventAdditions) {
			return;
		}
		$xoctEventAdditions = xoctEventAdditions::find($this->getIdentifier());
		if (!$xoctEventAdditions instanceof xoctEventAdditions) {
			$xoctEventAdditions = new xoctEventAdditions();
			$xoctEventAdditions->setId($this->getIdentifier());
		}
		$this->setXoctEventAdditions($xoctEventAdditions);
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
		$usage = $this->publication_usage_repository->getUsage(PublicationUsage::USAGE_LIVE_EVENT);
		if (!$usage) {
			return false;
		}
		$publication = $this->publications()->getPublicationMetadataForUsage($usage);
		return !empty($publication);
	}

	/**
	 * @throws xoctException
	 */
	protected function setCurrentUserAsPublisher() {
		$publisher = $this->getMetadata()->getField('publisher');
		$publisher->setValue(xoctUser::getInstance(self::dic()->user())->getIdentifier());
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