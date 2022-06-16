<?php

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

    /**
     * @var string
     */
    private $id;
    /**
     * @var MDDataType
     */
    private $type;
    /**
     * @var boolean
     */
    private $read_only;
    /**
     * @var boolean
     */
    private $required;

    /**
     * @param string $id
     * @param MDDataType $type
     * @param bool $read_only
     * @param bool $required
     */
    public function __construct(string $id, MDDataType $type, bool $read_only, bool $required)
    {
        $this->id = $id;
        $this->type = $type;
        $this->read_only = $read_only;
        $this->required = $required;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return MDDataType
     */
    public function getType(): MDDataType
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function isReadOnly(): bool
    {
        return $this->read_only;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }
}
