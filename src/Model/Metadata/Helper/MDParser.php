<?php

namespace srag\Plugins\Opencast\Model\Metadata\Helper;

use DateTime;
use DateTimeZone;
use Exception;
use ilTimeZone;
use srag\Plugins\Opencast\Model\API\Metadata\Metadata;
use srag\Plugins\Opencast\Model\API\Metadata\MetadataFactory;
use srag\Plugins\Opencast\Model\API\Metadata\MetadataField;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use srag\Plugins\Opencast\Model\Metadata\MetadataDIC;
use stdClass;
use xoctException;

class MDParser
{
    /**
     * @var MDCatalogueFactory
     */
    private $md_catalogue_factory;
    /**
     * @var MetadataFactory
     */
    private $metadata_factory;

    public function __construct(MetadataDIC $metadataDIC)
    {
        $this->md_catalogue_factory = $metadataDIC->catalogueFactory();
        $this->metadata_factory = $metadataDIC->metadataFactory();
    }


    /**
     * @throws xoctException
     */
    public function parseAPIResponseEvent(array $response) : Metadata
    {
        foreach ($response as $d) {
            if ($d->flavor == Metadata::FLAVOR_DUBLINCORE_EPISODES) {
                $fields = $d->fields;
                break;
            }
        }
        if (!isset($fields)) {
            throw new xoctException(xoctException::INTERNAL_ERROR,
                'Metadata for event could not be loaded.');
        }

        $catalogue = $this->md_catalogue_factory->event();
        $metadata = $this->metadata_factory->eventMetadata();
        foreach ($catalogue->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition->getId() == MDFieldDefinition::F_START_DATE) {
                // start can be in one or two fields, but we'll always store them in one field
                $key_start_date = array_search(MDFieldDefinition::F_START_DATE, array_column($fields, 'id'));
                $field = $fields[$key_start_date];
                $key_start_time = array_search(MDFieldDefinition::F_START_TIME, array_column($fields, 'id'));
                if ($key_start_time) {
                    $field_time = $fields[$key_start_time];
                    $field->value .= 'T' . $field_time->value . 'Z';
                }
            } else {
                $key = array_search($fieldDefinition->getId(), array_column($fields, 'id'));
                $field = $fields[$key];
            }
            $metadata->addField((new MetadataField($field->id, $fieldDefinition->getType()
            ))->withValue($this->formatMDValue($field->value, $fieldDefinition->getType())));
        }
        return $metadata;
    }

    public function parseAPIResponseSeries(array $data) : Metadata
    {

    }

    /**
     * @param $value
     * @param MDDataType $dataType
     * @return DateTime|mixed
     * @throws Exception
     */
    private function formatMDValue($value, MDDataType $dataType)
    {
        switch ($dataType->getTitle()) {
            case MDDataType::TYPE_DATE:
                $tz = new DateTimeZone(ilTimeZone::_getInstance()->getIdentifier());
                // TODO: time zone offset wrong..
                return new DateTime($value, $tz);
            case MDDataType::TYPE_TEXT:
            case MDDataType::TYPE_TEXT_LONG:
            case MDDataType::TYPE_TEXT_ARRAY:
            default:
                return $value;
        }
    }
}