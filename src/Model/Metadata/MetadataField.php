<?php

namespace srag\Plugins\Opencast\Model\Metadata;

use DateTimeImmutable;
use DateTimeZone;
use ilTimeZone;
use JsonSerializable;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use stdClass;
use xoctException;

/**
 * @template T
 */
class MetadataField implements JsonSerializable
{

    /**
     * @var string
     */
    protected $id;
    /**
     * @var T
     */
    protected $value;
    /**
     * @var MDDataType
     */
    private $type;

    /**
     * @return MDDataType
     */
    public function getType(): MDDataType
    {
        return $this->type;
    }

    /**
     * @param string $id
     * @param MDDataType $type
     * @throws xoctException
     */
    public function __construct(string $id, MDDataType $type)
    {
        if (strlen($id) == 0) {
            throw new xoctException(xoctException::INTERNAL_ERROR,
                "id of MetadataField cannot be empty");
        }
        $this->id = $id;
        $this->type = $type;
    }

    public function getId() : string
    {
        return $this->id;
    }

    /**
     * @return T|null
     */
    public function getValue()
    {
        return is_string($this->value) ? strip_tags($this->value) : $this->value;
    }

    /**
     * @return array|string
     * formats the value for the Opencast API
     * this format is also used for sortation in a table
     */
    public function getValueFormatted()
    {
        switch ($this->getType()->getTitle()) {
            case MDDataType::TYPE_TEXT:
            case MDDataType::TYPE_TEXT_LONG:
            case MDDataType::TYPE_TEXT_ARRAY:
                return $this->getValue();
            case MDDataType::TYPE_DATETIME;
                /** @var DateTimeImmutable|null $value */
                $value = $this->getValue();
                return $value ? $value->setTimezone(new DateTimeZone('utc'))->format('Y-m-d\TH:i:s\Z') : '';
            case MDDataType::TYPE_TIME:
                /** @var DateTimeImmutable|null $value */
                $value = $this->getValue();
                return $value ? $value->setTimezone(new DateTimeZone('utc'))->format('H:i:s\Z') : '';
            case MDDataType::TYPE_DATE:
                /** @var DateTimeImmutable|null $value */
                $value = $this->getValue();
                return $value ? $value->setTimezone(new DateTimeZone('utc'))->format('Y-m-d') : '';
        }
    }

    public function toString() : string
    {
        switch ($this->getType()->getTitle()) {
            case MDDataType::TYPE_TEXT:
            case MDDataType::TYPE_TEXT_LONG:
                return $this->getValue();
            case MDDataType::TYPE_TEXT_ARRAY:
                return implode(', ', $this->getValue());
            case MDDataType::TYPE_DATETIME;
                /** @var DateTimeImmutable|null $value */
                $value = $this->getValue();
                return $value ? $value->setTimezone(new DateTimeZone(ilTimeZone::_getDefaultTimeZone()))->format('d.m.Y H:i:s') : '';
            case MDDataType::TYPE_TIME:
                /** @var DateTimeImmutable|null $value */
                $value = $this->getValue();
                return $value ? $value->setTimezone(new DateTimeZone(ilTimeZone::_getDefaultTimeZone()))->format('H:i:s') : '';
            case MDDataType::TYPE_DATE:
                /** @var DateTimeImmutable|null $value */
                $value = $this->getValue();
                return $value ? $value->setTimezone(new DateTimeZone(ilTimeZone::_getDefaultTimeZone()))->format('d.m.Y') : '';
        }
    }

    /**
     * @param $value T
     * @throws xoctException
     */
    public function setValue($value)
    {
        if (!$this->type->isValidValue($value)) {
            $class = gettype($value) === 'object' ? get_class($value) : gettype($value);
            throw new xoctException(xoctException::INTERNAL_ERROR,
                "invalid value type $class for md type {$this->type->getTitle()}");
        }
        $this->value = $value;
    }

    /**
     * @param $value T
     * @return $this
     * @throws xoctException
     */
    public function withValue($value) : self
    {
        $clone = clone $this;
        $clone->setValue($value);
        return $clone;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function fixPercentCharacter(string $string) : string
    {
        // Bug in OpenCast server? The server think the JSON body is url encoded, but % is valid in JSON
        return str_replace('%', rawurlencode('%'), $string);
    }

    public function jsonSerialize()
    {
        $stdClass = new stdClass();
        $stdClass->id = $this->getId();

        $value = $this->getValueFormatted();
        if (is_string($value)) {
            $value = $this->fixPercentCharacter($value);
        }
        $stdClass->value = $value;

        return (array) $stdClass;
    }
}