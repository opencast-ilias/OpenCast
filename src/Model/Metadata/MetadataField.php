<?php

declare(strict_types=1);

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
    protected string $id;
    /**
     * @var mixed
     */
    protected $value;

    public function getType(): MDDataType
    {
        return $this->type;
    }

    /**
     * @throws xoctException
     */
    public function __construct(string $id, private MDDataType $type)
    {
        if ($id === '') {
            throw new xoctException(
                xoctException::INTERNAL_ERROR,
                "id of MetadataField cannot be empty"
            );
        }
        $this->id = $id;
    }

    public function getId(): string
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
            case MDDataType::TYPE_TIME:
            case MDDataType::TYPE_TEXT_SELECTION:
                return $this->getValue();
            case MDDataType::TYPE_DATETIME:
                /** @var DateTimeImmutable|null $value */
                $value = $this->getValue();
                return $value instanceof \DateTimeImmutable ? $value->setTimezone(new DateTimeZone('utc'))->format(
                    'Y-m-d\TH:i:s\Z'
                ) : '';
            case MDDataType::TYPE_DATE:
                /** @var DateTimeImmutable|null $value */
                $value = $this->getValue();
                return $value instanceof \DateTimeImmutable ? $value->setTimezone(new DateTimeZone('utc'))->format(
                    'Y-m-d'
                ) : '';
        }

        return '';
    }

    public function toString(): string
    {
        switch ($this->getType()->getTitle()) {
            case MDDataType::TYPE_TEXT:
            case MDDataType::TYPE_TIME:
            case MDDataType::TYPE_TEXT_LONG:
            case MDDataType::TYPE_TEXT_SELECTION:
                return $this->getValue();
            case MDDataType::TYPE_TEXT_ARRAY:
                return implode(', ', $this->getValue());
            case MDDataType::TYPE_DATETIME:
                /** @var DateTimeImmutable|null $value */
                $value = $this->getValue();
                return $value instanceof \DateTimeImmutable ? $value->setTimezone(
                    new DateTimeZone(ilTimeZone::_getDefaultTimeZone())
                )->format('d.m.Y H:i:s') : '';
            case MDDataType::TYPE_DATE:
                /** @var DateTimeImmutable|null $value */
                $value = $this->getValue();
                return $value instanceof \DateTimeImmutable ? $value->setTimezone(
                    new DateTimeZone(ilTimeZone::_getDefaultTimeZone())
                )->format('d.m.Y') : '';
        }

        return '';
    }

    /**
     * @param $value T
     */
    public function setValue($value): void
    {
        if (!$this->type->isValidValue($value)) {
            $class = gettype($value) === 'object' ? $value::class : gettype($value);
            throw new xoctException(
                xoctException::INTERNAL_ERROR,
                "invalid value type $class for md type {$this->type->getTitle()}"
            );
        }
        $this->value = $value;
    }

    /**
     * @param $value T
     */
    public function withValue($value): self
    {
        $clone = clone $this;
        $clone->setValue($value);
        return $clone;
    }

    protected function fixPercentCharacter(string $string): string
    {
        // Bug in OpenCast server? The server think the JSON body is url encoded, but % is valid in JSON
        return str_replace('%', rawurlencode('%'), $string);
    }

    public function jsonSerialize(): mixed
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
