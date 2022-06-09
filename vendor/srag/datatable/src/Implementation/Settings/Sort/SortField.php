<?php

namespace srag\DataTableUI\OpencastObject\Implementation\Settings\Sort;

use srag\DataTableUI\OpencastObject\Component\Settings\Sort\SortField as SortFieldInterface;
use srag\DataTableUI\OpencastObject\Component\Settings\Storage\SettingsStorage;
use srag\DataTableUI\OpencastObject\Implementation\Utils\DataTableUITrait;
use srag\DIC\OpencastObject\DICTrait;
use stdClass;

/**
 * Class SortField
 *
 * @package srag\DataTableUI\OpencastObject\Implementation\Settings\Sort
 */
class SortField implements SortFieldInterface
{

    use DICTrait;
    use DataTableUITrait;

    /**
     * @var string
     */
    protected $sort_field = "";
    /**
     * @var int
     */
    protected $sort_field_direction = 0;


    /**
     * SortField constructor
     *
     * @param string $sort_field
     * @param int    $sort_field_direction
     */
    public function __construct(string $sort_field, int $sort_field_direction)
    {
        $this->sort_field = $sort_field;

        $this->sort_field_direction = $sort_field_direction;
    }


    /**
     * @inheritDoc
     */
    public function getSortField() : string
    {
        return $this->sort_field;
    }


    /**
     * @inheritDoc
     */
    public function getSortFieldDirection() : int
    {
        return $this->sort_field_direction;
    }


    /**
     * @inheritDoc
     */
    public function jsonSerialize() : stdClass
    {
        return (object) [
            SettingsStorage::VAR_SORT_FIELD           => $this->sort_field,
            SettingsStorage::VAR_SORT_FIELD_DIRECTION => $this->sort_field_direction
        ];
    }


    /**
     * @inheritDoc
     */
    public function withSortField(string $sort_field) : SortFieldInterface
    {
        $clone = clone $this;

        $clone->sort_field = $sort_field;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withSortFieldDirection(int $sort_field_direction) : SortFieldInterface
    {
        $clone = clone $this;

        $clone->sort_field_direction = $sort_field_direction;

        return $clone;
    }
}
