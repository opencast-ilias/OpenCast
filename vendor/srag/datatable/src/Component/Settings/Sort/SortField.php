<?php

namespace srag\DataTableUI\OpenCast\Component\Settings\Sort;

use JsonSerializable;
use stdClass;

/**
 * Interface SortField
 *
 * @package srag\DataTableUI\OpenCast\Component\Settings\Sort
 */
interface SortField extends JsonSerializable
{

    /**
     * @var int
     */
    const SORT_DIRECTION_DOWN = 2;
    /**
     * @var int
     */
    const SORT_DIRECTION_UP = 1;


    /**
     * @return string
     */
    public function getSortField() : string;


    /**
     * @return int
     */
    public function getSortFieldDirection() : int;


    /**
     * @inheritDoc
     *
     * @return stdClass
     */
    public function jsonSerialize() : stdClass;


    /**
     * @param string $sort_field
     *
     * @return self
     */
    public function withSortField(string $sort_field) : self;


    /**
     * @param int $sort_field_direction
     *
     * @return self
     */
    public function withSortFieldDirection(int $sort_field_direction) : self;
}
