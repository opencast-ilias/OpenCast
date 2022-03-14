<?php

namespace srag\Plugins\Opencast\Model\Metadata\Definition;

class MDFieldDefinition
{
    const F_TITLE = 'title';
    const F_DESCRIPTION = 'description';
    const F_SUBJECTS = 'subjects';
    const F_LANGUAGE = 'language';
    const F_RIGHTS_HOLDER = 'rightsHolder';
    const F_LICENSE = 'license';
    const F_IS_PART_OF = 'isPartOf';
    const F_CREATOR = 'creator';
    const F_CONTRIBUTOR = 'contributor';
    const F_START_DATE = 'startDate';
    const F_START_TIME = 'startTime';
    const F_DURATION = 'duration';
    const F_SOURCE = 'source';
    const F_CREATED = 'created';
    const F_PUBLISHER = 'publisher';
    const F_IDENTIFIER = 'identifier';
    const F_CREATED_BY = 'createdBy';
    const F_LOCATION = 'location';

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