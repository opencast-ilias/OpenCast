<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Request/class.xoctRequest.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Object/class.xoctObject.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Object/class.xoctMetadata.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/Acl/class.xoctAcl.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/Properties/class.xoctProperties.php');

/**
 * Class xoctSeries
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctSeries extends xoctObject {

	/**
	 * @param string $identifier
	 */
	public function __construct($identifier = '') {
		if ($identifier) {
			$this->setIdentifier($identifier);
			$this->read();
		}
	}


	protected function afterObjectLoad() {
	}


	public function loadMetadata() {
		if ($this->getIdentifier()) {
			$data = json_decode(xoctRequest::root()->series($this->getIdentifier())->metadata()->get());
			foreach ($data as $d) {
				if ($d->flavor == xoctMetadata::FLAVOR_DUBLINCORE_SERIES) {
					$xoctMetadata = new xoctMetadata();
					$xoctMetadata->loadFromStdClass($d);
					$this->setMetadata($xoctMetadata);
				}
			}
		}
	}


	public function read() {
		$data = json_decode(xoctRequest::root()->series($this->getIdentifier())->get());
		$this->loadFromStdClass($data);
		$this->loadMetadata();
		$this->updateFieldsFromMetadata();
		$this->loadProperties();
	}


	public function create() {
		$this->setMetadata(xoctMetadata::getSet(xoctMetadata::FLAVOR_DUBLINCORE_SERIES));
		$this->updateMetadataFromFields();
		$array['metadata'] = json_encode(array( $this->getMetadata()->__toStdClass() ));
		$array['acl'] = json_encode(array( xoctAcl::userRead(), xoctAcl::adminWrite(), xoctAcl::adminWrite() ));
		$array['theme'] = $this->getTheme();

		$data = json_decode(xoctRequest::root()->series()->post($array, 'fschmid@unibe.ch'));
		if ($data) {
			$this->setIdentifier($data);
		} else {
			$this->setIdentifier(time()); // TODO set correct identifier
		}
	}


	public function update() {
		$this->loadMetadata();
		$this->updateMetadataFromFields();
		$array['metadata'] = json_encode(array(
			$this->getMetadata()->getField('title')->__toStdClass(),
			$this->getMetadata()->getField('description')->__toStdClass(),
			$this->getMetadata()->getField('license')->__toStdClass(),
		));

		xoctRequest::root()->series($this->getIdentifier())->metadata()->parameter('type', $this->getMetadata()->getFlavor())->put($array);

		$this->loadProperties();
		$array = array(
			'properties' => json_encode($this->getProperties()->__toStdClass())
		);

		xoctRequest::root()->series($this->getIdentifier())->properties()->put($array);

		self::removeFromCache($this->getIdentifier());
	}


	protected function updateMetadataFromFields() {
		$this->getMetadata()->getField('title')->setValue($this->getTitle());
		$this->getMetadata()->getField('description')->setValue($this->getDescription());
		$this->getMetadata()->getField('license')->setValue($this->getLicense() ? $this->getLicense() : '-');
	}


	protected function updateFieldsFromMetadata() {
		$this->setTitle($this->getMetadata()->getField('title')->getValue());
		$this->setDescription($this->getMetadata()->getField('description')->getValue());
		$this->setLicense($this->getMetadata()->getField('license')->getValue());
	}


	public function delete() {
		// TODO: Implement delete() method.
	}


	/**
	 * @return xoctSeries[]
	 */
	public static function getAll() {
		$return = array();
		$data = json_decode(xoctRequest::root()->series()->get());
		foreach ($data as $d) {
			$obj = new self();
			$obj->loadFromStdClass($d);
			$return[] = $obj;
		}

		return $return;
	}


	/**
	 * @param $user_string
	 *
	 * @return xoctSeries[]
	 */
	public static function getAllForUser($user_string) {
		$return = array();
		$data = json_decode(xoctRequest::root()->series()->get($user_string));
		foreach ($data as $d) {
			$obj = new self();
			$obj->loadFromStdClass($d);
			$return[] = $obj;
		}

		return $return;
	}


	/**
	 * @var string
	 */
	protected $identifier = '';
	/**
	 * @var string
	 */
	public $title;
	/**
	 * @var string
	 */
	public $description;
	/**
	 * @var array
	 */
	public $subjects;
	/**
	 * @var string
	 */
	public $creator;
	/**
	 * @var xoctAcl[]
	 */
	public $access_policies;
	/**
	 * @var DateTime
	 */
	public $created;
	/**
	 * @var array
	 */
	public $organizers;
	/**
	 * @var array
	 */
	public $contributors;
	/**
	 * @var array
	 */
	public $publishers;
	/**
	 * @var bool
	 */
	protected $opt_out = false;
	/**
	 * @var string
	 */
	public $license = NULL;
	/**
	 * @var xoctMetadata
	 */
	protected $metadata = array();
	/**
	 * @var xoctProperties
	 */
	protected $properties = NULL;
	/**
	 * @var int
	 */
	protected $theme = 0;


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
	 * @return xoctAcl[]
	 */
	public function getAccessPolicies() {
		return $this->access_policies;
	}


	/**
	 * @param xoctAcl[] $access_policies
	 */
	public function setAccessPolicies($access_policies) {
		$this->access_policies = $access_policies;
	}


	/**
	 * @return DateTime
	 */
	public function getCreated() {
		return $this->created;
	}


	/**
	 * @param DateTime $created
	 */
	public function setCreated($created) {
		$this->created = $created;
	}


	/**
	 * @return array
	 */
	public function getOrganizers() {
		return $this->organizers;
	}


	/**
	 * @param array $organizers
	 */
	public function setOrganizers($organizers) {
		$this->organizers = $organizers;
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
	 * @return array
	 */
	public function getPublishers() {
		return $this->publishers;
	}


	/**
	 * @param array $publishers
	 */
	public function setPublishers($publishers) {
		$this->publishers = $publishers;
	}


	/**
	 * @return boolean
	 */
	public function isOptOut() {
		return $this->opt_out;
	}


	/**
	 * @param boolean $opt_out
	 */
	public function setOptOut($opt_out) {
		$this->opt_out = $opt_out;
	}


	/**
	 * @return string
	 */
	public function getLicense() {
		return $this->license;
	}


	/**
	 * @param string $license
	 */
	public function setLicense($license) {
		$this->license = $license;
	}


	/**
	 * @return xoctMetadata
	 */
	public function getMetadata() {
		return $this->metadata;
	}


	/**
	 * @param xoctMetadata $metadata
	 */
	public function setMetadata($metadata) {
		$this->metadata = $metadata;
	}


	/**
	 * @return xoctProperties
	 */
	public function getProperties() {
		return $this->properties;
	}


	/**
	 * @param xoctProperties $properties
	 */
	public function setProperties($properties) {
		$this->properties = $properties;
	}


	/**
	 * @return int
	 */
	public function getTheme() {
		return $this->theme;
	}


	/**
	 * @param int $theme
	 */
	public function setTheme($theme) {
		$this->theme = $theme;
	}


	protected function loadProperties() {
		$data = json_decode(xoctRequest::root()->series($this->getIdentifier())->properties()->get());
		$xoctProperties = new xoctProperties();
		$xoctProperties->loadFromStdClass($data);
		$this->setProperties($xoctProperties);
		$this->updateFieldsFromProperties();
	}


	protected function updateFieldsFromProperties() {
		$this->setTheme($this->getProperties()->getTheme());
	}
}