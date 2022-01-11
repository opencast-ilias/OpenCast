<?php

use srag\Plugins\Opencast\Model\API\APIObject;

/**
 * Class xoctMetadata
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class Metadata extends APIObject
{

    /**
     * @param $flavor
     *
     * @return Metadata
     */
    public static function getSet(string $flavor) : Metadata
    {
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
     * @var MetadataField[]
     */
    protected $fields = array();


    /**
     * @param $field_name
     *
     * @return MetadataField
     */
    public function getField(string $field_name) : MetadataField
    {
        foreach ($this->getFields() as $field) {
            if ($field->getId() == $field_name) {
                return $field;
            }
        }
        $field = new MetadataField();
        $field->setId($field_name);
        $this->addField($field);

        return $field;
    }


    /**
     * @param MetadataField $xoctMetadataField
     *
     * @return bool
     */
    public function addOrReplaceField(MetadataField $xoctMetadataField) : bool
    {
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
    public function removeField(string $field_name) : bool
    {
        foreach ($this->getFields() as $i => $field) {
            if ($field->getId() == $field_name) {
                unset($this->fields[$i]);
                sort($this->fields);

                return true;
            }
        }

        return false;
    }


    public function read()
    {
    }


    public function update()
    {
    }


    public function create()
    {
    }


    public function delete()
    {
    }


    protected function afterObjectLoad()
    {
        //		$arr = $this->getFields();
        //		foreach ($arr as $a) {
        //
        //		}
    }


    /**
     * @param array $array
     */
    public function loadFromArray(array $array)
    {
        parent::loadFromArray($array);
        $fields = array();
        foreach ($this->getFields() as $f) {
            $field = new MetadataField();
            $field->loadFromStdClass($f);
            $fields[] = $field;
        }
        sort($fields);
        $this->setFields($fields);
    }


    /**
     * @param MetadataField $xoctMetadataField
     */
    public function addField(MetadataField $xoctMetadataField)
    {
        $this->fields[] = $xoctMetadataField;
        sort($this->fields);
    }


    /**
     * @return string
     */
    public function getFlavor()
    {
        return $this->flavor;
    }


    /**
     * @param string $flavor
     */
    public function setFlavor($flavor)
    {
        $this->flavor = $flavor;
    }


    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }


    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }


    /**
     * @return MetadataField[]
     */
    public function getFields()
    {
        return $this->fields;
    }


    /**
     * @param MetadataField[] $fields
     */
    public function setFields($fields)
    {
        $this->fields = $fields;
    }
}

/**
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class MetadataField extends APIObject
{

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
    public function getId()
    {
        return $this->id;
    }


    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }


    /**
     * @return string
     */
    public function getReadOnly()
    {
        return $this->read_only;
    }


    /**
     * @param string $read_only
     */
    public function setReadOnly($read_only)
    {
        $this->read_only = $read_only;
    }


    /**
     * @return string
     */
    public function getValue()
    {
        return is_string($this->value) ? strip_tags($this->value) : $this->value;
    }


    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }


    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }


    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }


    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }


    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }


    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }


    /**
     * @param boolean $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }


    /**
     * @return array
     */
    public function getCollection()
    {
        return $this->collection;
    }


    /**
     * @param array $collection
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;
    }


    /**
     * @return stdClass
     */
    public function __toStdClass() : stdClass
    {
        $stdClass = new stdClass();
        $stdClass->id = $this->getId();

        $value = $this->getValue();
        if (is_string($value)) {
            $value = $this->fixPercentCharacter($value);
        }
        $stdClass->value = $value;

        return $stdClass;
    }


    /**
     * @return string
     */
    public function __toXML() : string
    {

    }
}

?>
