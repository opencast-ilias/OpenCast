<?php

namespace srag\Plugins\Opencast\Model\Metadata\Definition;

class MDDataType
{
    const TYPE_TEXT = 'text';
    const TYPE_TEXT_LONG = 'text_long';
    const TYPE_DATE = 'date';
    const TYPE_TEXT_ARRAY = 'text_array';
    const TYPE_TIME = 'time';

    /**
     * @var string
     */
    private $title;

    /**
     * @param string $title
     */
    private function __construct(string $title)
    {
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

    public static function date(): self
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

}