<?php

namespace srag\Plugins\Opencast\Model\Metadata\Definition;

class MDFieldDefinition
{
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