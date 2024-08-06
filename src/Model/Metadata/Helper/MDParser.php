<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Metadata\Helper;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use ilTimeZone;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;
use srag\Plugins\Opencast\Model\Metadata\Metadata;
use srag\Plugins\Opencast\Model\Metadata\MetadataFactory;
use srag\Plugins\Opencast\Model\Metadata\MetadataField;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDFieldDefinition;
use xoctException;

class MDParser
{
    public function __construct(private readonly MDCatalogueFactory $catalogueFactory, private readonly MetadataFactory $metadataFactory)
    {
    }

    /**
     * @throws xoctException
     */
    public function getMetadataFromResponse(array $response): Metadata
    {
        foreach ($response as $d) {
            if ($d->flavor == Metadata::FLAVOR_DUBLINCORE_EPISODES) {
                $fields = $d->fields;
                break;
            }
        }
        if (!isset($fields)) {
            throw new xoctException(
                xoctException::INTERNAL_ERROR,
                'Metadata for event could not be loaded.'
            );
        }
        return $this->parseAPIResponseEvent($fields);
    }

    /**
     * @throws \xoctException
     */
    public function getMetadataFromData(\stdClass $data): Metadata
    {
        $fields = [];

        foreach ($data as $key => $entry) {
            $field_data = match ($key) {
                "presenter" => (object) [
                    "id" => "creator",
                    "value" => $entry
                ],
                "creator" => (object) [
                    "id" => "publisher",
                    "value" => $entry
                ],
                "is_part_of" => (object) [
                    "id" => "isPartOf",
                    "value" => $entry
                ],
                "start" => (object) [
                    "id" => "startDate",
                    "value" => $entry
                ],
                default => (object) [
                    "id" => $key,
                    "value" => $entry
                ],
            };
            if (!in_array($field_data, $fields, true)) {
                $fields[] = $field_data;
            }
        }
        return $this->parseAPIResponseEvent($fields);
    }

    protected function parseAPIResponseEvent(array $fields): Metadata
    {
        $catalogue = $this->catalogueFactory->event();
        $metadata = $this->metadataFactory->event();
        return $this->parseAPIResponseGeneric($fields, $metadata, $catalogue);
    }

    public function parseAPIResponseSeries(array $response): Metadata
    {
        foreach ($response as $d) {
            if ($d->flavor === Metadata::FLAVOR_DUBLINCORE_SERIES) {
                $fields = $d->fields;
                break;
            }
        }
        if (!isset($fields)) {
            throw new xoctException(
                xoctException::INTERNAL_ERROR,
                'Metadata for series could not be loaded.'
            );
        }

        $catalogue = $this->catalogueFactory->series();
        $metadata = $this->metadataFactory->series();
        return $this->parseAPIResponseGeneric($fields, $metadata, $catalogue);
    }

    private function parseAPIResponseGeneric(array $fields, Metadata $metadata, MDCatalogue $catalogue): Metadata
    {
        foreach ($catalogue->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition->getId() === MDFieldDefinition::F_START_DATE) {
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
                if ($key === false) {
                    continue;
                }
                $field = $fields[$key];
            }
            $metadata->addField(
                (new MetadataField(
                    $field->id,
                    $fieldDefinition->getType()
                ))->withValue($this->formatMDValueFromAPIResponse($field->value, $fieldDefinition->getType()))
            );
        }
        return $metadata;
    }

    /**
     * @param $value
     * @return DateTime|mixed
     * @throws Exception
     */
    private function formatMDValueFromAPIResponse($value, MDDataType $dataType)
    {
        switch ($dataType->getTitle()) {
            case MDDataType::TYPE_DATETIME:
                $tz = new DateTimeZone(ilTimeZone::_getDefaultTimeZone());
                return new DateTimeImmutable($value, $tz);
            case MDDataType::TYPE_TIME:
                if (is_int($value)) {
                    return date("H:i:s", $value);
                }
                if (is_string($value)) {
                    return $value;
                }
                // no break
            case MDDataType::TYPE_TEXT:
            case MDDataType::TYPE_TEXT_LONG:
                return (string) $value;
            case MDDataType::TYPE_TEXT_ARRAY:
                if (!is_array($value)) {
                    return [$value];
                }
                // no break
            default:
                return $value;
        }
        return null;
    }

    /**
     * @throws xoctException
     */
    public function parseFormDataEvent(array $data): Metadata
    {
        $metadata = $this->metadataFactory->event();
        $catalogue = $this->catalogueFactory->event();
        return $this->parseFormData($data, $metadata, $catalogue);
    }

    public function parseFormDataSeries(array $data): Metadata
    {
        $metadata = $this->metadataFactory->series();
        $catalogue = $this->catalogueFactory->series();
        return $this->parseFormData($data, $metadata, $catalogue);
    }

    private function parseFormData(array $data, Metadata $metadata, MDCatalogue $catalogue): Metadata
    {
        foreach (
            array_filter($data, fn($key): bool => str_starts_with($key, 'md_'), ARRAY_FILTER_USE_KEY)
            as $id => $value
        ) {
            $id = substr($id, 3);
            $definition = $catalogue->getFieldById($id);
            if ($definition->isReadOnly()) {
                continue;
            }
            if ($id === MDFieldDefinition::F_START_DATE) {
                // start date must be split up into startDate and startTime for the OC api
                $field = new MetadataField($id, MDDataType::date());
                $time_field = (new MetadataField(MDFieldDefinition::F_START_TIME, MDDataType::time()));
                $time_field = $value ? $time_field->withValue(
                    $value->setTimezone(new DateTimeZone('GMT'))->format('H:i:s')
                ) : $time_field;
                $metadata->addField($time_field);
            } else {
                $field = new MetadataField($id, $definition->getType());
            }
            // todo: remove this if-clause as soon as this is fixed: https://mantis.ilias.de/view.php?id=31966
            if ($value && $definition->getType()->getTitle() === MDDataType::TYPE_TEXT_ARRAY && !is_array($value)) {
                $value = explode(',', (string) $value);
            }
            $metadata->addField(
                $value ? $field->withValue(
                    $value
                ) : $field
            );
        }
        return $metadata;
    }
}
