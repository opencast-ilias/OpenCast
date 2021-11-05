<?php

namespace srag\Plugins\Opencast\Model\API\Metadata;

use srag\Plugins\Opencast\Model\Metadata\Definition\MDDataType;
use stdClass;
use xoctException;

/**
 * @template T
 */
class MetadataField
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
     * @return T
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param $value T
     * @throws xoctException
     */
    public function setValue($value)
    {
        if (!$this->type->isValidValue($value)) {
            $class = get_class($value);
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
     * @return stdClass
     */
    public function __toStdClass(): stdClass
    {
        $stdClass = new stdClass();
        $stdClass->id = $this->getId();

        $value = $this->getValue();
        if (is_string($value)) {
            $value = $this->fixPercentCharacter($value);
        }
        $stdClass->value = $value;

        return $stdClass;
    }
//    /**
//     * @param $start
//     * @throws ilTimeZoneException
//     */
//    public function setStart($start) {
//        $date_time_zone = new DateTimeZone(ilTimeZone::_getInstance()->getIdentifier());
//        if ($start instanceof DateTime) {
//            $start->setTimezone($date_time_zone);
//            $this->start = $start;
//        } else {
//            $this->start = new DateTime($start, $date_time_zone);
//        }
//    }
//
//    /**
//     * @param null $input
//     * @return DateTime
//     */
//    public function getDefaultDateTimeObject($input = null) {
//        if ($input instanceof DateTime) {
//            $input = $input->format(DATE_ATOM);
//        }
//        if (!$input) {
//            $input = 'now';
//        }
//        try {
//            $timezone = new DateTimeZone(ilTimeZone::_getInstance()->getIdentifier());
//        } catch (ilException $e) {
//            $timezone = null;
//        }
//
//        $datetime = is_int($input) ? new DateTime(date('Y-m-d H:i:s', $input)) : new DateTime($input);
//        $datetime->setTimezone($timezone);
//        return $datetime;
//    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function fixPercentCharacter(string $string) : string
    {
        // TODO: Bug in OpenCast server? The server think the JSON body is url encoded, but % is valid in JSON
        return str_replace('%', rawurlencode('%'), $string);
    }
}