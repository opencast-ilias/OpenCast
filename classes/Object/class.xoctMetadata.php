<?php

/**
 * Class xoctMetadata
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctMetadata extends xoctObject {

	/**
	 * @param $flavor
	 *
	 * @return xoctMetadata
	 */
	public static function getSet($flavor) {
		$obj = new self();
		$obj->setFlavor($flavor);

		return $obj;
	}


	const FLAVOR_DUBLINCORE_SERIES = "dublincore/series";
	const FLAVOR_DUBLINCORE_EPISODES = "dublincore/episode";
	const FLAVOR_PRESENTER_PLAYER_PREVIEW = "presenter/player+preview";
	const FLAVOR_PRESENTATION_PLAYER_PREVIEW = "presentation/player+preview";
	const FLAVOR_PRESENTATION_SEGMENT_PREVIEW_HIGHRES = "presentation/segment+preview+highres";
	const FLAVOR_PRESENTATION_SEGMENT_PREVIEW_LOWRES = "presentation/segment+preview+lowres";
	const FLAVOR_PRESENTER_SEGMENT_PREVIEW_HIGHRES = "presenter/segment+preview+highres";
	const FLAVOR_PRESENTER_SEGMENT_PREVIEW_LOWRES = "presenter/segment+preview+lowres";
	const FLAVOR_PRESENTATION_SEGMENT_PREVIEW = "presentation/segment+preview";
	const FLAVOR_PRESENTER_SEGMENT_PREVIEW = "presenter/segment+preview";
	/**
	 * @var string
	 */
	protected $label = '';
	/**
	 * @var string
	 */
	protected $flavor = '';
	/**
	 * @var xoctMetadataField[]
	 */
	protected $fields = array();


	/**
	 * @param $field_name
	 *
	 * @return xoctMetadataField
	 */
	public function getField($field_name) {
		foreach ($this->getFields() as $field) {
			if ($field->getId() == $field_name) {
				return $field;
			}
		}
		$field = new xoctMetadataField();
		$field->setId($field_name);
		$this->addField($field);

		return $field;
	}


	/**
	 * @param xoctMetadataField $xoctMetadataField
	 *
	 * @return bool
	 */
	public function addOrReplaceField(xoctMetadataField $xoctMetadataField) {
		foreach ($this->getFields() as $k => $f) {
			if ($f->getId() == $xoctMetadataField->getId()) {
				$this->fields[$k] = $xoctMetadataField;
			}

			return true;
		}
		$this->addField($xoctMetadataField);

		return false;
	}


	/**
	 * @param $field_name
	 *
	 * @return bool
	 */
	public function removeField($field_name) {
		foreach ($this->getFields() as $i => $field) {
			if ($field->getId() == $field_name) {
				unset($this->fields[$i]);
				sort($this->fields);

				return true;
			}
		}

		return false;
	}


	public function read() {
		// TODO: Implement read() method.
	}


	public function update() {
		// TODO: Implement update() method.
	}


	public function create() {
		// TODO: Implement create() method.
	}


	public function delete() {
		// TODO: Implement delete() method.
	}


	protected function afterObjectLoad() {
		//		$arr = $this->getFields();
		//		foreach ($arr as $a) {
		//
		//		}
	}


	public function loadFromArray($array) {
		parent::loadFromArray($array);
		$fields = array();
		foreach ($this->getFields() as $f) {
			$field = new xoctMetadataField();
			$field->loadFromArray($f);
			$fields[] = $field;
		}
		sort($fields);
		$this->setFields($fields);
	}


	/**
	 * @param xoctMetadataField $xoctMetadataField
	 */
	public function addField(xoctMetadataField $xoctMetadataField) {
		$this->fields[] = $xoctMetadataField;
		sort($this->fields);
	}


	/**
	 * @return string
	 */
	public function getFlavor() {
		return $this->flavor;
	}


	/**
	 * @param string $flavor
	 */
	public function setFlavor($flavor) {
		$this->flavor = $flavor;
	}


	/**
	 * @return string
	 */
	public function getLabel() {
		return $this->label;
	}


	/**
	 * @param string $label
	 */
	public function setLabel($label) {
		$this->label = $label;
	}


	/**
	 * @return xoctMetadataField[]
	 */
	public function getFields() {
		return $this->fields;
	}


	/**
	 * @param xoctMetadataField[] $fields
	 */
	public function setFields($fields) {
		$this->fields = $fields;
	}
}

/**
 * Class xoctMetadataField
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class xoctMetadataField extends xoctObject {

	/**
	 * @var string
	 */
	protected $id = '';
	/**
	 * @var string
	 */
	protected $read_only = false;
	/**
	 * @var string
	 */
	protected $value = '';
	/**
	 * @var string
	 */
	protected $label = '';
	/**
	 * @var string
	 */
	protected $type = '';
	/**
	 * @var bool
	 */
	protected $required = false;
	/**
	 * @var array
	 */
	protected $collection = array();


	/**
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @param string $id
	 */
	public function setId($id) {
		$this->id = $id;
	}


	/**
	 * @return string
	 */
	public function getReadOnly() {
		return $this->read_only;
	}


	/**
	 * @param string $read_only
	 */
	public function setReadOnly($read_only) {
		$this->read_only = $read_only;
	}


	/**
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}


	/**
	 * @param string $value
	 */
	public function setValue($value) {
		$this->value = $value;
	}


	/**
	 * @return string
	 */
	public function getLabel() {
		return $this->label;
	}


	/**
	 * @param string $label
	 */
	public function setLabel($label) {
		$this->label = $label;
	}


	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}


	/**
	 * @param string $type
	 */
	public function setType($type) {
		$this->type = $type;
	}


	/**
	 * @return boolean
	 */
	public function isRequired() {
		return $this->required;
	}


	/**
	 * @param boolean $required
	 */
	public function setRequired($required) {
		$this->required = $required;
	}


	/**
	 * @return array
	 */
	public function getCollection() {
		return $this->collection;
	}


	/**
	 * @param array $collection
	 */
	public function setCollection($collection) {
		$this->collection = $collection;
	}


	/**
	 * @return stdClass
	 */
	public function __toStdClass() {
		$stdClass = new stdClass();
		$stdClass->id = $this->getId();
		$stdClass->value = $this->getValue();

		return $stdClass;
	}
}

?>
