<?php

use srag\Plugins\Opencast\Cache\CacheFactory;
use srag\Plugins\Opencast\Model\ACL\ACL;
use srag\Plugins\Opencast\Model\ACL\ACLEntry;
use srag\Plugins\Opencast\Model\API\APIObject;
use srag\Plugins\Opencast\Model\Metadata\Metadata;

/**
 * Class xoctSeries
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctSeries extends APIObject {


    /**
     * @throws xoctException
     */
    public function loadMetadata() {
		if ($this->getIdentifier()) {
			$data = json_decode(xoctRequest::root()->series($this->getIdentifier())->metadata()->get());
			foreach ($data as $d) {
				if ($d->flavor == Metadata::FLAVOR_DUBLINCORE_SERIES) {
                    Metadata::load($d);
					$this->setMetadata($xoctMetadata);
				}
			}
		}
	}


    /**
     * @throws xoctException
     */
    public function read() {
		$data = json_decode(xoctRequest::root()->series($this->getIdentifier())->get());
		$this->loadFromStdClass($data);
		$this->loadMetadata();
		$this->updateFieldsFromMetadata();
	}

    /**
     * @throws xoctException
     */
    public function update() {
		$this->loadMetadata();
		$this->updateMetadataFromFields();
		$array['metadata'] = json_encode(array(
			$this->getMetadata()->getField('title')->__toStdClass(),
			$this->getMetadata()->getField('description')->__toStdClass(),
			$this->getMetadata()->getField('license')->__toStdClass(),
			$this->getMetadata()->getField('identifier')->__toStdClass(),
			$this->getMetadata()->getField('creator')->__toStdClass(),
			$this->getMetadata()->getField('contributor')->__toStdClass(),
			// identifier is needed as workaround
		));

		xoctRequest::root()->series($this->getIdentifier())->metadata()->parameter('type', $this->getMetadata()->getFlavor())->put($array);

		// when creating objects with existing series, the access policies are empty (=no change)
		if ($this->getAccessPolicies()) {
			$array = array(
				'acl' => json_encode($this->getAccessPolicies())
			);
			xoctRequest::root()->series($this->getIdentifier())->acl()->put($array);
		}

		self::removeFromCache($this->getIdentifier());
	}


    /**
     *
     */
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

		$organizers = $this->getMetadata()->getField('creator');
        $organizers->setValue($this->getOrganizers());
		$this->getMetadata()->addOrReplaceField($organizers);

		$contributors = $this->getMetadata()->getField('contributor');
        $contributors->setValue($this->getContributors());
		$this->getMetadata()->addOrReplaceField($contributors);
	}


    /**
     *
     */
    protected function updateFieldsFromMetadata() {
		$this->setTitle($this->getMetadata()->getField('title')->getValue());
		$this->setDescription($this->getMetadata()->getField('description')->getValue());
		$this->setLicense($this->getMetadata()->getField('license')->getValue());
		$this->setOrganizers($this->getMetadata()->getField('creator')->getValue());
		$this->setContributors($this->getMetadata()->getField('contributor')->getValue());
	}


    /**
     *
     */
    public function delete() {
		xoctRequest::root()->series($this->identifier)->delete();
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
	 * @var ACL
	 */
	public $access_policies;
	/**
	 * @var DateTime
	 */
	public $created;
	/**
	 * @var array
	 */
	public $organizers = array();
	/**
	 * @var array
	 */
	public $contributors = array();
	/**
	 * @var array
	 */
	public $publishers = array();
	/**
	 * @var bool
	 */
	protected $opt_out = false;
	/**
	 * @var string
	 */
	public $license = NULL;
	/**
	 * @var Metadata
	 */
	protected $metadata = array();
	/**
	 * @var int
	 */
	protected $theme;


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


	public function getAccessPolicies() : ACL
    {
		return $this->access_policies;
	}

	public function setAccessPolicies(ACL $access_policies) : void
    {
		$this->access_policies = $access_policies;
	}


	/**
	 * @param ACLEntry $access_policy
	 */
	public function addAccessPolicy(ACLEntry $access_policy) {
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
	 * @return Metadata
	 */
	public function getMetadata() {
		return $this->metadata;
	}


	/**
	 * @param Metadata $metadata
	 */
	public function setMetadata($metadata) {
		$this->metadata = $metadata;
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

    /**
     * @return int
     */
	public function getPermissionTemplateId() {
		$template = xoctPermissionTemplate::getTemplateForAcls($this->getAccessPolicies());
		return $template ? $template->getId() : 0;
	}

    /**
     * @return bool
     */
	public function isPublishedOnVideoPortal() {
        $template = xoctPermissionTemplate::getTemplateForAcls($this->getAccessPolicies());
	    return $template && !$template->isDefault();
    }
}
