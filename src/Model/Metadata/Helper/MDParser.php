<?php

namespace srag\Plugins\Opencast\Model\Metadata\Helper;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use ilTimeZone;
use srag\Plugins\Opencast\Model\API\Metadata\Metadata;
use srag\Plugins\Opencast\Model\API\Metadata\MetadataFactory;
use srag\Plugins\Opencast\Model\API\Metadata\MetadataField;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use xoctException;

class MDParser
{
    /**
     * @var MDCatalogueFactory
     */
    private $catalogueFactory;
    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    public function __construct(MDCatalogueFactory $catalogueFactory, MetadataFactory $metadataFactory)
    {
        $this->catalogueFactory = $catalogueFactory;
        $this->metadataFactory = $metadataFactory;
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

        $catalogue = $this->catalogueFactory->event();
        $metadata = $this->metadataFactory->event();
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
            ))->withValue($this->formatMDValueFromAPIResponse($field->value, $fieldDefinition->getType())));
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
    private function formatMDValueFromAPIResponse($value, MDDataType $dataType)
    {
        switch ($dataType->getTitle()) {
            case MDDataType::TYPE_DATETIME:
                $tz = new DateTimeZone(ilTimeZone::_getInstance()->getIdentifier());
                // TODO: time zone offset wrong..
                return new DateTimeImmutable($value, $tz);
            case MDDataType::TYPE_TEXT_ARRAY:
            case MDDataType::TYPE_TEXT:
            case MDDataType::TYPE_TEXT_LONG:
            default:
                return $value;
        }
    }

    private function formatMDValueFromForm($value, MDDataType $dataType)
    {

    }

    /**
     * @param array $data
     * @return Metadata
     * @throws xoctException
     */
    public function parseFormDataEvent(array $data) : Metadata
    {
        $metadata = $this->metadataFactory->event();
        $catalogue = $this->catalogueFactory->event();
        foreach ($data as $id => $value) {
            $definition = $catalogue->getFieldById($id);
            if ($id == MDFieldDefinition::F_START_DATE) {
                // start date must be split up into startDate and startTime for the OC api
                $field = new MetadataField($id, MDDataType::date());
                /** @var DateTimeImmutable $value */
                $time_field = (new MetadataField(MDFieldDefinition::F_START_TIME, MDDataType::time()))
                    ->withValue($value);
                $metadata->addField($time_field);
            } else {
                $field = new MetadataField($id, $definition->getType());
            }
            $metadata->addField($field->withValue($value));
        }
        return $metadata;
    }
}