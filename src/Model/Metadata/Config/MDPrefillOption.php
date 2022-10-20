<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config;

use xoctException;

class MDPrefillOption
{
    public const T_COURSE_TITLE = 'crs_title';
    public const T_USERNAME_OF_CREATOR = 'username_creator';
    public const T_NONE = 'none';

    public static $allowed_values = [
        self::T_COURSE_TITLE,
        self::T_USERNAME_OF_CREATOR,
        self::T_NONE
    ];

    /**
     * @var string
     */
    private $value;

    /**
     * @param string|null $value
     * @throws xoctException
     */
    public function __construct(?string $value)
    {
        if ($value && !in_array($value, self::$allowed_values)) {
            throw new xoctException(
                xoctException::INTERNAL_ERROR,
                $value . " is not an allowed value for MDPrefillOption"
            );
        }
        $this->value = $value ?? self::T_NONE;
    }

    public static function course_title(): self
    {
        return new self(self::T_COURSE_TITLE);
    }

    public static function username_of_creator(): self
    {
        return new self(self::T_USERNAME_OF_CREATOR);
    }

    public static function none(): self
    {
        return new self(self::T_NONE);
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
