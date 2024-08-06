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
    private static array $types = [
        self::TYPE_TEXT,
        self::TYPE_TEXT_LONG,
        self::TYPE_TEXT_ARRAY,
        self::TYPE_DATETIME,
        self::TYPE_DATE,
        self::TYPE_TIME,
        self::TYPE_TEXT_SELECTION,
    ];

    /**
     * @readonly
     */
    private string $title;

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
        return match ($this->getTitle()) {
            self::TYPE_TEXT_SELECTION, self::TYPE_TEXT, self::TYPE_TEXT_LONG => is_string($value),
            self::TYPE_TIME => is_string($value),
            self::TYPE_DATETIME, self::TYPE_DATE => $value instanceof DateTimeImmutable,
            self::TYPE_TEXT_ARRAY => is_array($value),
            default => throw new xoctException(
                xoctException::INTERNAL_ERROR,
                "invalid MDDataType for " . $this->getTitle()
            ),
        };
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
