<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Metadata\Definition;

class MDFieldDefinition
{
    public const F_TITLE = 'title';
    public const F_DESCRIPTION = 'description';
    public const F_SUBJECTS = 'subjects';
    public const F_LANGUAGE = 'language';
    public const F_RIGHTS_HOLDER = 'rightsHolder';
    public const F_LICENSE = 'license';
    public const F_IS_PART_OF = 'isPartOf';
    public const F_CREATOR = 'creator';
    public const F_CONTRIBUTOR = 'contributor';
    public const F_START_DATE = 'startDate';
    public const F_START_TIME = 'startTime';
    public const F_DURATION = 'duration';
    public const F_SOURCE = 'source';
    public const F_CREATED = 'created';
    public const F_PUBLISHER = 'publisher';
    public const F_IDENTIFIER = 'identifier';
    public const F_CREATED_BY = 'createdBy';
    public const F_LOCATION = 'location';

    private string $id;
    private MDDataType $type;
    private bool $read_only;
    private bool $required;
    private bool $mandatory;

    public function __construct(
        string $id,
        MDDataType $type,
        bool $read_only,
        bool $required,
        bool $mandatory = false
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->read_only = $read_only;
        $this->required = $required;
        $this->mandatory = $mandatory;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getType(): MDDataType
    {
        return $this->type;
    }

    public function isReadOnly(): bool
    {
        return $this->read_only;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function isMandatory(): bool
    {
        return $this->mandatory;
    }
}
