<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Metadata;

use JsonSerializable;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogue;
use stdClass;
use xoctException;

/**
 * Class xoctMetadata
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class Metadata implements JsonSerializable
{
    public const FLAVOR_DUBLINCORE_SERIES = "dublincore/series";
    public const FLAVOR_DUBLINCORE_EPISODES = "dublincore/episode";
    public const FLAVOR_PRESENTER_PLAYER_PREVIEW = "presenter/player+preview";
    public const FLAVOR_PRESENTATION_PLAYER_PREVIEW = "presentation/player+preview";
    public const FLAVOR_PRESENTATION_SEGMENT_PREVIEW_HIGHRES = "presentation/segment+preview+highres";
    public const FLAVOR_PRESENTATION_SEGMENT_PREVIEW_LOWRES = "presentation/segment+preview+lowres";
    public const FLAVOR_PRESENTER_SEGMENT_PREVIEW_HIGHRES = "presenter/segment+preview+highres";
    public const FLAVOR_PRESENTER_SEGMENT_PREVIEW_LOWRES = "presenter/segment+preview+lowres";
    public const FLAVOR_PRESENTATION_SEGMENT_PREVIEW = "presentation/segment+preview";
    public const FLAVOR_PRESENTER_SEGMENT_PREVIEW = "presenter/segment+preview";

    protected MDCatalogue $md_catalogue;
    protected string $title;
    protected string $flavor;
    /**
     * @var MetadataField[]
     */
    protected $fields = [];

    public function __construct(MDCatalogue $md_catalogue, string $title, string $flavor)
    {
        $this->md_catalogue = $md_catalogue;
        $this->title = $title;
        $this->flavor = $flavor;
    }

    /**
     * @throws xoctException
     */
    public function getField(string $field_name): MetadataField
    {
        foreach ($this->getFields() as $field) {
            if ($field->getId() === $field_name) {
                return $field;
            }
        }
        $field = new MetadataField($field_name, $this->md_catalogue->getFieldById($field_name)->getType());
        $this->addField($field);

        return $field;
    }

    /**
     * @param $field_name
     */
    public function removeField(string $field_name): bool
    {
        foreach ($this->getFields() as $i => $field) {
            if ($field->getId() === $field_name) {
                unset($this->fields[$i]);
                sort($this->fields);

                return true;
            }
        }

        return false;
    }

    public function addField(MetadataField $metadataField): void
    {
        $this->fields[] = $metadataField;
        sort($this->fields);
    }

    public function getFlavor(): string
    {
        return $this->flavor;
    }


    public function setFlavor(string $flavor): void
    {
        $this->flavor = $flavor;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return MetadataField[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param MetadataField[] $fields
     */
    public function setFields(array $fields): void
    {
        $this->fields = $fields;
    }

    public function withoutEmptyFields(): self
    {
        $clone = clone $this;
        $clone->fields = array_values(
            array_filter($clone->fields, static function (MetadataField $field): bool {
                // no nulls, no empty strings, no empty arrays
                return (bool) $field->getValue();
            })
        );
        return $clone;
    }

    public function jsonSerialize()
    {
        $std_class = new stdClass();
        $std_class->label = $this->getTitle();
        $std_class->flavor = $this->getFlavor();
        $std_class->fields = array_map(static function (MetadataField $field): array {
            return $field->jsonSerialize();
        }, $this->getFields());
        return $std_class;
    }
}
