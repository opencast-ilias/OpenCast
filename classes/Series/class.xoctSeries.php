<?php
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Request/class.xoctRequest.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Object/class.xoctObject.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Object/class.xoctMetadata.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/Acl/class.xoctAcl.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Series/Properties/class.xoctProperties.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/IVTGroup/class.xoctUser.php');
require_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/classes/Cache/class.xoctCacheFactory.php');

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
		$data = json_decode(xoctRequest::root()->series($this->getIdentifier())->acl()->get());
		$acls = array();
		foreach ($data as $d) {
			$p = new xoctAcl();
			$p->loadFromStdClass($d);
			$acls[] = $p;
		}
		$this->setAccessPolicies($acls);
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
//		$this->loadProperties();
	}


	/**
	 * @param array $xoctUsers
	 */
	public function addProducers(array $xoctUsers) {
		foreach ($xoctUsers as $xoctUser) {
			$this->addProducer($xoctUser, true);
		}
		$this->update();
	}


	/**
	 * @param xoctUser|string $xoctUser
	 * @param bool     $omit_update
	 */
	public function addProducer($xoctUser, $omit_update = false) {
		if ($xoctUser instanceof xoctUser) {
			$xoctUser = $xoctUser->getUserRoleName();
		}

		if (!$xoctUser) {
			return false;
		}

		$already_has_read = false;
		$already_has_write = false;
		foreach ($this->getAccessPolicies() as $acl) {
			if ($acl->getRole() == $xoctUser) {
				if ($acl->getAction() == xoctAcl::READ) {
					$already_has_read = true;
				} else if ($acl->getAction() == xoctAcl::WRITE) {
					$already_has_write = true;
				}
			}
		}

		if (!$already_has_read) {
			$new_read_acl = new xoctAcl();
			$new_read_acl->setAction(xoctAcl::READ);
			$new_read_acl->setAllow(true);
			$new_read_acl->setRole($xoctUser);
			$this->addAccessPolicy($new_read_acl);
		}

		if (!$already_has_write) {
			$new_write_acl = new xoctAcl();
			$new_write_acl->setAction(xoctAcl::WRITE);
			$new_write_acl->setAllow(true);
			$new_write_acl->setRole($xoctUser);
			$this->addAccessPolicy($new_write_acl);
		}

		if (!$omit_update && (!$already_has_read || !$already_has_write)) {
			$this->update();
			return true;
		}

		return false;
	}


	public function create() {
		$metadata = xoctMetadata::getSet(xoctMetadata::FLAVOR_DUBLINCORE_SERIES);
		$metadata->setLabel('Opencast Series DublinCore');
		$this->setMetadata($metadata);
		$this->updateMetadataFromFields();
		$this->getMetadata()->removeField('identifier'); // the identifier metadata lead to double creation of series on cast

		$array['metadata'] = json_encode(array(
			$this->getMetadata()->__toStdClass(),
		));

		foreach ($this->getAccessPolicies() as $acl) {
			$acls[] = $acl->__toStdClass();
		}
		$array['acl'] = json_encode($acls);
		$array['theme'] = $this->getTheme();

//		echo $array['acl'];
//		exit;
//		foreach ($array as $k => $item) {
//			echo $k . ':<br>';
//			echo $item;
//
//			echo '<br>';
//			echo '<br>';
//		}
//
//		exit;

		$data = json_decode(xoctRequest::root()->series()->post($array));

		if ($data->identifier) {
			$this->setIdentifier($data->identifier);
		} else {
			throw new xoctException(xoctException::API_CREATION_FAILED);
		}
	}


	public function update() {
		$this->loadMetadata();
		$this->updateMetadataFromFields();
		$array['metadata'] = json_encode(array(
			$this->getMetadata()->getField('title')->__toStdClass(),
			$this->getMetadata()->getField('description')->__toStdClass(),
			$this->getMetadata()->getField('license')->__toStdClass(),
			$this->getMetadata()->getField('identifier')->__toStdClass(),
			// identifier is needed as workaround
		));

		xoctRequest::root()->series($this->getIdentifier())->metadata()->parameter('type', $this->getMetadata()->getFlavor())->put($array);

//		$this->loadProperties();
//		$array = array(
//			'properties' => json_encode($this->getProperties()->__toStdClass())
//		);
//
//		xoctRequest::root()->series($this->getIdentifier())->properties()->put($array);

		// when creating objects with existing series, the access policies are empty (=no change)
		if ($this->getAccessPolicies()) {
			$array = array(
				'acl' => json_encode($this->getAccessPolicies())
			);
			xoctRequest::root()->series($this->getIdentifier())->acl()->put($array);
		}

		self::removeFromCache($this->getIdentifier());
	}


	protected function updateMetadataFromFields() {
		$title = $this->getMetadata()->getField('title');
		$title->setValue($this->getTitle());
		$this->getMetadata()->addOrReplaceField($title);

		$description = $this->getMetadata()->getField('description');
		$description->setValue($this->getDescription());
		$this->getMetadata()->addOrReplaceField($description);

		$license = $this->getMetadata()->getField('license');
		$license->setValue($this->getLicense() ? $this->getLicense() : '-');
		$this->getMetadata()->addOrReplaceField($license);

		$subjects = $this->getMetadata()->getField('identifier');
		$subjects->setValue($this->getIdentifier());
		$this->getMetadata()->addOrReplaceField($subjects);
	}


	protected function updateFieldsFromMetadata() {
		$this->setTitle($this->getMetadata()->getField('title')->getValue());
		$this->setDescription($this->getMetadata()->getField('description')->getValue());
		$this->setLicense($this->getMetadata()->getField('license')->getValue());
	}


	public function delete() {
		xoctRequest::root()->series($this->identifier)->delete();
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
		if ($existing = xoctCacheFactory::getInstance()->get('series-' . $user_string)) {
			return $existing;
		}
		$return = array();
		$data = (array) json_decode(xoctRequest::root()->series()->get('', array( $user_string )));
		foreach ($data as $d) {
			$obj = new self();
			try {
				$obj->loadFromStdClass($d);
				$return[] = $obj;
			} catch (xoctException $e) {    // it's possible that the current user has access to more series than the configured API user
				continue;
			}
		}
		xoctCacheFactory::getInstance()->set('series-' . $user_string, $return, 60);

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
	protected $theme = 1234;


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
	 * @param xoctAcl $access_policy
	 */
	public function addAccessPolicy(xoctAcl $access_policy) {
		$this->access_policies[] = $access_policy;
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


//	/**
//	 * @return xoctProperties
//	 */
//	public function getProperties() {
//		return $this->properties;
//	}
//
//
//	/**
//	 * @param xoctProperties $properties
//	 */
//	public function setProperties($properties) {
//		$this->properties = $properties;
//	}


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


//	protected function loadProperties() {
//		$data = json_decode(xoctRequest::root()->series($this->getIdentifier())->properties()->get());
//		$xoctProperties = new xoctProperties();
//		$xoctProperties->loadFromStdClass($data);
//		$this->setProperties($xoctProperties);
//		$this->updateFieldsFromProperties();
//	}
//
//
//	protected function updateFieldsFromProperties() {
//		$this->setTheme($this->getProperties()->getTheme());
//	}
}