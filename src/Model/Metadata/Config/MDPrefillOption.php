<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config;

use xoctException;

class MDPrefillOption
{
    const T_COURSE_TITLE = 'crs_title';
    const T_USERNAME_OF_CREATOR = 'username_creator';

    private static $allowed_values = [
        self::T_COURSE_TITLE,
        self::T_USERNAME_OF_CREATOR
    ];

    /**
     * @var string
     */
    private $value;

    /**
     * @param string $value
     * @throws xoctException
     */
    public function __construct(string $value)
    {
        if (!in_array($value, self::$allowed_values)) {
            throw new xoctException(xoctException::INTERNAL_ERROR,
                $value . " is not an allowed value for MDPrefillOption");
        }
        $this->value = $value;
    }

    public function course_title() : self
    {
        return new self(self::T_COURSE_TITLE);
    }

    public function username_of_creator() : self
    {
        return new self(self::T_USERNAME_OF_CREATOR);
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

}