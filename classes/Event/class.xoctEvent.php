<?php
/**
 * Class xoctEvent
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctEvent extends xoctObject {

	public static $LOAD_MD_SEPARATE = true;
	public static $LOAD_ACL_SEPARATE = false;
	public static $LOAD_PUB_SEPARATE = true;
	public static $NO_METADATA = false;
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
	const NO_PREVIEW = './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/images/no_preview.png';
	const THUMBNAIL_SCHEDULED = './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/images/thumbnail_scheduled.svg';
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
	);
	/**
	 * @var string
	 */
	protected $thumbnail_url = null;
	/**
	 * @var string
	 */
	protected $annotation_url = null;
	/**
	 * @var string
	 */
	protected $player_url = null;
	/**
	 * @var null
	 */
	protected $download_url = null;
	/**
	 * @var xoctEventAdditions
	 */
	protected $xoctEventAdditions = null;


	/**
	 * @param $identifier
	 *
	 * @return xoctEvent
	 */
	public static function find($identifier) {
		/**
		 * @var $xoctEvent xoctEvent
		 */
		$xoctEvent = parent::find($identifier);
		$xoctEvent->afterObjectLoad();

		return $xoctEvent;
	}


    /**
     * @param array $filter
     * @param null $for_user
     * @param null $for_role
     * @param int $from
     * @param int $to
     * @return array
     * @throws xoctException
     */
	public static function getFiltered(array $filter, $for_user = null, $for_role = null, $from = 0, $to = 99999) {
		/**
		 * @var $xoctEvent xoctEvent
		 */
		$request = xoctRequest::root()->events();
		if ($filter) {
			$filter_string = '';
			foreach ($filter as $k => $v) {
				$filter_string .= $k . ':' . $v . '';
			}

			$request->parameter('filter', $filter_string);
		}
		$request->parameter('limit', 1000);

		if (self::$LOAD_MD_SEPARATE || self::$NO_METADATA) {
			$request->parameter('withmetadata', false);
		} else {
			$request->parameter('withmetadata', true);
		}

		if (!self::$LOAD_ACL_SEPARATE) {
			$request->parameter('withacl', true);
		}

		if (!self::$LOAD_PUB_SEPARATE) {
			$request->parameter('withpublications', true);
		}

		if (xoct::isApiVersionGreaterThan('v1.1.0')){
            $request->parameter('withscheduling', true);
        }

		$data = json_decode($request->get());
		$return = array();

		foreach ($data as $d) {
			$xoctEvent = xoctEvent::findOrLoadFromStdClass($d->identifier, $d);
			if (!in_array($xoctEvent->getProcessingState(), array( self::STATE_SUCCEEDED, self::STATE_OFFLINE, ))) {
				self::removeFromCache($xoctEvent->getIdentifier());
			}
			$return[] = $xoctEvent->getArrayForTable();
		}

		return $return;
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
			xoctLog::getInstance()->writeTrace();
			$data = json_decode(xoctRequest::root()->events($this->getIdentifier())->get());
			$this->loadFromStdClass($data);
		}
		$this->afterObjectLoad();
	}


	/**
	 *
	 */
	public function afterObjectLoad() {
		if (!$this->getPublications() && !$this->isScheduled()) {
			$this->loadPublications();
		}

		if (!$this->getAcl()) {
			$this->loadAcl();
		}

		$this->initProcessingState();

		if ($this->isScheduled() && !$this->scheduling) {
			$this->loadScheduling();
		}

		if (!$this->getXoctEventAdditions()) {
			$this->initAdditions();
		}

		$this->loadMetadata();

		// if no_metadata option is set, the metadata below will already be initialized
		if (self::$NO_METADATA) {
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
     * @return array|DateTime|mixed|string|xoctMetadata
     * @throws xoctException
     */
	protected function wakeup($fieldname, $value) {
		switch ($fieldname) {
			case 'created':
			case 'start_time':
				return $this->getDefaultDateTimeObject($value);
				break;
			case 'metadata':
				$metadata = new xoctMetadata();
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
				$publications = array();
				foreach ($value as $p_array) {
					$md = new xoctPublication();
					$md->loadFromArray($p_array);
					$publications[] = $md;
				}

				return $publications;
			case 'presenter':
				return implode(self::PRESENTER_SEP, $value);
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
     * @param bool $auto_publish
     * @throws xoctException
     */
	public function create($auto_publish = false) {
		global $DIC;
		$ilUser = $DIC['ilUser'];
		$data = array();

		$this->setMetadata(xoctMetadata::getSet(xoctMetadata::FLAVOR_DUBLINCORE_EPISODES));
		$this->setOwner(xoctUser::getInstance($ilUser));
		$this->updateMetadataFromFields(false);

		$data['metadata'] = json_encode(array( $this->getMetadata()->__toStdClass() ));
		$data['processing'] = json_encode($this->getProcessing($auto_publish));
		$data['acl'] = json_encode($this->getAcl());

		$presenter = xoctUploadFile::getInstanceFromFileArray('file_presenter');
		$data['presenter'] = $presenter->getCURLFile();
		//		for ($x = 0; $x < 50; $x ++) { // Use this to upload 50 Clips at once, for testing
		$return = json_decode(xoctRequest::root()->events()->post($data));
		//		}

		$this->setIdentifier($return->identifier);
	}


	/**
	 *
	 */
    public function schedule($rrule = '', $omit_set_owner = false) {
        if (!$omit_set_owner) {
            global $DIC;
            $ilUser = $DIC['ilUser'];
            $this->setOwner(xoctUser::getInstance($ilUser));
        }

        $data = array();

        $this->setMetadata(xoctMetadata::getSet(xoctMetadata::FLAVOR_DUBLINCORE_EPISODES));
        $this->updateMetadataFromFields(true);
        $this->updateSchedulingFromFields();

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
		$this->getMetadata()->removeField('identifier');
		$this->getMetadata()->removeField('isPartOf');
		$this->getMetadata()->removeField('createdBy'); // can't be updated at the moment

		$data['metadata'] = json_encode(array( $this->getMetadata()->__toStdClass() ));

		if ($this->isScheduled()) {
			$this->updateSchedulingFromFields();
			if ($this->getScheduling()->hasChanged()) {
                $data['scheduling'] = json_encode( $this->getScheduling()->__toStdClass());
            }
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

		xoctRequest::root()->events($this->getIdentifier())->acl()->put(array( 'acl' => json_encode($this->getAcl()) ));
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
		foreach ($this->getAcl() as $i => $acl) {
			if (strpos($acl->getRole(), str_replace('{IDENTIFIER}', '', xoctUser::getOwnerRolePrefix())) !== false) {
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
	 * @return string
	 */
	public function getThumbnailUrl() {
		if (in_array($this->getProcessingState(), array(self::STATE_SCHEDULED, self::STATE_SCHEDULED_OFFLINE, self::STATE_RECORDING))) {
			$this->thumbnail_url = self::THUMBNAIL_SCHEDULED;
			return $this->thumbnail_url;
		}

		$possible_publications = array(
			xoctPublicationUsage::USAGE_THUMBNAIL,
			xoctPublicationUsage::USAGE_THUMBNAIL_FALLBACK,
			xoctPublicationUsage::USAGE_THUMBNAIL_FALLBACK_2,
		);
		$i = 0;
		while (!$this->thumbnail_url && $i < count($possible_publications)) {
			$url = $this->getFirstPublicationMetadataForUsage(xoctPublicationUsage::find($possible_publications[$i]))->getUrl();
			if (xoctConf::getConfig(xoctConf::F_SIGN_THUMBNAIL_LINKS)) {
				$this->thumbnail_url = xoctSecureLink::sign($url);
			} else {
				$this->thumbnail_url = $url;
			}
			$i ++;
		}
		if (!$this->thumbnail_url) {
			$this->thumbnail_url = self::NO_PREVIEW;
		}

		return $this->thumbnail_url;
	}


	/**
	 * @return null|string
	 */
	public function getAnnotationLink() {
		if (!isset($this->annotation_url)) {
			$url = $this->getFirstPublicationMetadataForUsage(xoctPublicationUsage::find(xoctPublicationUsage::USAGE_ANNOTATE))->getUrl();
			if (xoctConf::getConfig(xoctConf::F_SIGN_ANNOTATION_LINKS)) {
				$this->annotation_url = xoctSecureLink::sign($url);
			} else {

				$this->annotation_url = $url;
			}
		}

		return $this->annotation_url;
	}


	/**
	 * @return null|string
	 */
	public function getPlayerLink() {
		if (!isset($this->player_url)) {
			$url = $this->getFirstPublicationMetadataForUsage(xoctPublicationUsage::find(xoctPublicationUsage::USAGE_PLAYER))->getUrl();
			if (xoctConf::getConfig(xoctConf::F_SIGN_PLAYER_LINKS)) {
				$this->player_url = xoctSecureLink::sign($url);
			} else {
				$this->player_url = $url;
			}
		}

		return $this->player_url;
	}


	/**
	 * @return null|string
	 */
	public function getDownloadLink() {
		if (!isset($this->download_url)) {
			$url = $this->getFirstPublicationMetadataForUsage(xoctPublicationUsage::find(xoctPublicationUsage::USAGE_DOWNLOAD))->getUrl();
			if (xoctConf::getConfig(xoctConf::F_SIGN_DOWNLOAD_LINKS)) {
				$this->download_url = xoctSecureLink::sign($url);
			} else {
				$this->download_url = $url;
			}
		}

		return $this->download_url;
	}


	/**
	 * @return null|string
	 */
	public function getCuttingLink() {
		if (!isset($this->cutting_url)) {
			$url = str_replace('{event_id}', $this->getIdentifier(), xoctConf::getConfig(xoctConf::F_EDITOR_LINK));
			if (!$url) {
				$url = $this->getFirstPublicationMetadataForUsage(xoctPublicationUsage::find(xoctPublicationUsage::USAGE_CUTTING))->getUrl();
			}
			if (!$url) {
				$base = rtrim(xoctConf::getConfig(xoctConf::F_API_BASE), "/");
				$base = str_replace('/api', '', $base);
				$this->cutting_url = $base . '/external-url/events/' . $this->getIdentifier() . '/editor';
			}

			$this->cutting_url = $url;
		}

		return $this->cutting_url;
	}


	/**
	 * @param $xoctPublicationUsage
	 *
	 * @return array
	 */
	public function getPublicationMetadataForUsage($xoctPublicationUsage) {
		if (!$xoctPublicationUsage instanceof xoctPublicationUsage) {
			return [new xoctPublication()];
		}
		/**
		 * @var $xoctPublicationUsage  xoctPublicationUsage
		 * @var $attachment            xoctAttachment
		 * @var $media                 xoctMedia
		 */
		$medias = [];
		$attachments = [];
		foreach ($this->getPublications() as $publication) {
			if ($publication->getChannel() == $xoctPublicationUsage->getChannel()) {
				$medias = array_merge($medias, $publication->getMedia());
				$attachments = array_merge($attachments, $publication->getAttachments());
			}
		}
		$return = [];
		switch ($xoctPublicationUsage->getMdType()) {
			case xoctPublicationUsage::MD_TYPE_ATTACHMENT:
				foreach ($attachments as $attachment) {
					if ($attachment->getFlavor() == $xoctPublicationUsage->getFlavor()) {
						$return[] = $attachment;
					}
				}
				break;
			case xoctPublicationUsage::MD_TYPE_MEDIA:
				foreach ($medias as $media) {
					if ($media->getFlavor() == $xoctPublicationUsage->getFlavor()) {
						$return[] = $media;
					}
				}
				break;
			case xoctPublicationUsage::MD_TYPE_PUBLICATION_ITSELF:
				foreach ($this->getPublications() as $publication) {
					if ($publication->getChannel() == $xoctPublicationUsage->getChannel()) {
						$return[] = $publication;
					}
				}
				break;
			default:
				return [new xoctPublication()];
		}
		return $return;
	}


	/**
	 * @param $xoctPublicationUsage
	 *
	 * @return mixed|xoctPublication
	 */
	public function getFirstPublicationMetadataForUsage($xoctPublicationUsage) {
		$metadata = $this->getPublicationMetadataForUsage($xoctPublicationUsage);
		return count($metadata) ? array_shift($metadata) : new xoctPublication();
	}


	/**
	 *
	 */
	protected function loadPublications() {
		$data = json_decode(xoctRequest::root()->events($this->getIdentifier())->publications()->get());

		$publications = array();
		foreach ($data as $d) {
			$p = new xoctPublication();
			$p->loadFromStdClass($d);
			$publications[] = $p;
		}
		$this->setPublications($publications);
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
		if ($this->getIdentifier() && !self::$NO_METADATA) {
			$data = json_decode(xoctRequest::root()->events($this->getIdentifier())->metadata()->get());
			if (is_array($data)) {
				foreach ($data as $d) {
					if ($d->flavor == xoctMetadata::FLAVOR_DUBLINCORE_EPISODES) {
						$xoctMetadata = new xoctMetadata();
						$xoctMetadata->loadFromStdClass($d);
						$this->setMetadata($xoctMetadata);
					}
				}
			}
		}
		if (!$this->metadata) {
			$this->setMetadata(xoctMetadata::getSet(xoctMetadata::FLAVOR_DUBLINCORE_EPISODES));
		}
	}


	/**
	 *
	 */
	public function loadScheduling() {
		if ($this->getIdentifier()) {
			$this->scheduling = new xoctScheduling($this->getIdentifier());
			$this->setStart($this->scheduling->getStart());
			$this->setEnd($this->scheduling->getEnd());
			$this->setLocation($this->scheduling->getAgentId());
		} else {
			$this->scheduling = new xoctScheduling();
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
					$publication_player = xoctPublicationUsage::getUsage(xoctPublicationUsage::USAGE_PLAYER);

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
                    $this->setProcessingState(self::STATE_RECORDING);
                } elseif (!$this->getXoctEventAdditions()->getIsOnline()) {
					$this->setProcessingState(self::STATE_SCHEDULED_OFFLINE);
				} else {
					$this->setProcessingState(self::STATE_SCHEDULED);
				}
				break;
		}

		$this->processing_state_init = true;
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
	 * @var xoctPublication[]
	 */
	protected $publications;
	/**
	 * @var xoctMetadata
	 */
	protected $metadata = null;
	/**
	 * @var xoctAcl[]
	 */
	protected $acl = array();
	/**
	 * @var xoctScheduling
	 */
	protected $scheduling = null;
	/**
	 * @var string
	 */
	protected $series_identifier = '';
	/**
	 * @var string
	 */
	protected $owner_username = '';
	/**
	 * @var string
	 */
	protected $source = '';


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
	 * @return xoctPublication[]
	 */
	public function getPublications() {
		return $this->publications;
	}


	/**
	 * @param xoctPublication[] $publications
	 */
	public function setPublications($publications) {
		$this->publications = $publications;
	}


	/**
	 * @return xoctMetadata
	 */
	public function getMetadata() {
		if (!$this->metadata) {
			$this->loadMetadata();
		}
		return $this->metadata;
	}


	/**
	 * @param xoctMetadata $metadata
	 */
	public function setMetadata(xoctMetadata $metadata) {
		$this->metadata = $metadata;
	}


	/**
	 * @return xoctAcl[]
	 */
	public function getAcl() {
		return $this->acl;
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
	 * @return xoctScheduling
	 */
	public function getScheduling() {
		if (!$this->scheduling) {
			$this->loadScheduling();
		}
		return $this->scheduling;
	}


	/**
	 * @param xoctScheduling $scheduling
	 */
	public function setScheduling($scheduling) {
		$this->scheduling = $scheduling;
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
	 *
	 */
	protected function updateMetadataFromFields($scheduled) {
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
            $startTime->setValue($start->format('H:i'));
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
	 * @return \DateTime
	 */
	public function getDefaultDateTimeObject($input = null) {
		global $DIC;
		$ilIliasIniFile = $DIC['ilIliasIniFile'];
		if ($input instanceof DateTime) {
			$input = $input->format(DATE_ATOM);
		}
		if (!$input) {
			$input = 'now';
		}
		try {
			$timezone = new DateTimeZone($ilIliasIniFile->readVariable('server', 'timezone'));
		} catch (Exception $e) {
			$timezone = null;
		}

		$datetime = new DateTime($input);
		$datetime->setTimezone($timezone);
		return $datetime;
	}




	/**
	 * @param $fieldname
	 * @param $value
	 *
	 * @return mixed
	//	 */
	//	protected function sleep($fieldname, $value) {
	//		switch ($fieldname) {
	//			case 'presenter':
	//				return explode(self::PRESENTER_SEP, $value);
	//			default:
	//				return $value;
	//		}
	//	}

	/**
	 * @param bool|false $auto_publish
	 * @return stdClass
	 */
	protected function getProcessing($auto_publish = false) {
		require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Conf/class.xoctConf.php');
		$processing = new stdClass();
		$processing->workflow = xoctConf::getConfig(xoctConf::F_WORKFLOW);
		$processing->configuration = new stdClass();
		$processing->configuration->flagForCutting = 'false';
		$processing->configuration->flagForReview = 'false';
		$processing->configuration->publishToEngage = 'false';
		$processing->configuration->publishToHarvesting = 'false';
		$processing->configuration->straightToPublishing = 'true';
		$processing->configuration->publishToApi = 'true';
		$processing->configuration->autopublish = $auto_publish ? 'true' : 'false';

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
		return in_array($this->getProcessingState(), array(self::STATE_SCHEDULED, self::STATE_SCHEDULED_OFFLINE, self::STATE_RECORDING));
	}
}