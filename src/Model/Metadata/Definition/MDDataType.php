<?php

namespace srag\Plugins\Opencast\Model\Metadata\Definition;

use DateTimeImmutable;
use xoctException;

class MDDataType
{
    const TYPE_TEXT = 'text';
    const TYPE_TEXT_LONG = 'text_long';
    const TYPE_DATETIME = 'datetime';
    const TYPE_DATE = 'date';
    const TYPE_TEXT_ARRAY = 'text_array';
    const TYPE_TIME = 'time';
    private static $types = [
        self::TYPE_TEXT,
        self::TYPE_TEXT_LONG,
        self::TYPE_TEXT_ARRAY,
        self::TYPE_DATETIME,
        self::TYPE_DATE,
        self::TYPE_TIME
    ];

    /**
     * @var string
     */
    private $title;

    /**
     * @param string $title
     * @throws xoctException
     */
    public function __construct(string $title)
    {
        if (!in_array($title, self::$types)) {
            throw new xoctException(xoctException::INTERNAL_ERROR,
                "{$title} is not a valid MDDataType");
        }
        $this->title = $title;
    }

    public static function text(): self
    {
        return new self(self::TYPE_TEXT);
    }

    public static function text_array(): self
    {
        return new self(self::TYPE_TEXT_ARRAY);
    }

    public static function text_long(): self
    {
        return new self(self::TYPE_TEXT_LONG);
    }

    public static function datetime(): self
    {
        return new self(self::TYPE_DATETIME);
    }

    public static function date() : self
    {
        return new self(self::TYPE_DATE);
    }

    public static function time(): self
    {
        return new self(self::TYPE_TIME);
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @throws xoctException
     */
    public function isValidValue($value) : bool
    {
        switch ($this->getTitle()) {
            case self::TYPE_TEXT:
            case self::TYPE_TEXT_LONG:
                return is_string($value);
            case self::TYPE_TIME:
            case self::TYPE_DATETIME:
            case self::TYPE_DATE:
                return ($value instanceof DateTimeImmutable);
            case self::TYPE_TEXT_ARRAY:
                return is_array($value);
            default:
                throw new xoctException(xoctException::INTERNAL_ERROR,
                    "invalid MDDataType: " . get_class($value));
        }
    }

}