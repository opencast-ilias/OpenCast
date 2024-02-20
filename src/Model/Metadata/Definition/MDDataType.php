<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\Model\Metadata\Definition;

use DateTimeImmutable;
use xoctException;

class MDDataType
{
    public const TYPE_TEXT = 'text';
    public const TYPE_TEXT_LONG = 'text_long';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_DATE = 'date';
    public const TYPE_TEXT_ARRAY = 'text_array';
    public const TYPE_TEXT_SELECTION = 'text_selection';
    public const TYPE_TIME = 'time';
    private static $types = [
        self::TYPE_TEXT,
        self::TYPE_TEXT_LONG,
        self::TYPE_TEXT_ARRAY,
        self::TYPE_DATETIME,
        self::TYPE_DATE,
        self::TYPE_TIME,
        self::TYPE_TEXT_SELECTION,
    ];

    /**
     * @var string
     */
    private $title;

    /**
     * @throws xoctException
     */
    public function __construct(string $title)
    {
        if (!in_array($title, self::$types)) {
            throw new xoctException(
                xoctException::INTERNAL_ERROR,
                "{$title} is not a valid MDDataType"
            );
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

    public static function text_selection(): self
    {
        return new self(self::TYPE_TEXT_SELECTION);
    }

    public static function text_long(): self
    {
        return new self(self::TYPE_TEXT_LONG);
    }

    public static function datetime(): self
    {
        return new self(self::TYPE_DATETIME);
    }

    public static function date(): self
    {
        return new self(self::TYPE_DATE);
    }

    public static function time(): self
    {
        return new self(self::TYPE_TIME);
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @throws xoctException
     */
    public function isValidValue($value): bool
    {
        switch ($this->getTitle()) {
            case self::TYPE_TEXT_SELECTION:
            case self::TYPE_TEXT:
            case self::TYPE_TEXT_LONG:
                return is_string($value);
            case self::TYPE_TIME:
                return is_string($value);// && (empty($value) || preg_match("/^(?:2[0-3]|[01]\\d):[0-5]\\d:[0-5]\\d\$/", $value));
            case self::TYPE_DATETIME:
            case self::TYPE_DATE:
                return ($value instanceof DateTimeImmutable);
            case self::TYPE_TEXT_ARRAY:
                return is_array($value);
            default:
                throw new xoctException(
                    xoctException::INTERNAL_ERROR,
                    "invalid MDDataType for " . $this->getTitle()
                );
        }
    }

    public function isFilterable(): bool
    {
        return in_array($this->getTitle(), [
            self::TYPE_TEXT,
            self::TYPE_TEXT_LONG,
            self::TYPE_TEXT_ARRAY,
        ]);
    }
}
