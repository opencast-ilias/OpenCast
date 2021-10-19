<?php

namespace srag\DataTableUI\OpenCast\Component\Column;

use srag\DataTableUI\OpenCast\Component\Column\Formatter\Formatter;
use srag\DataTableUI\OpenCast\Component\Settings\Sort\SortField;

/**
 * Interface Column
 *
 * @package srag\DataTableUI\OpenCast\Component\Column
 */
interface Column
{

    /**
     * @return int
     */
    public function getDefaultSortDirection() : int;


    /**
     * @return Formatter
     */
    public function getFormatter() : Formatter;


    /**
     * @return string
     */
    public function getKey() : string;


    /**
     * @return string
     */
    public function getTitle() : string;


    /**
     * @return bool
     */
    public function isDefaultSelected() : bool;


    /**
     * @return bool
     */
    public function isDefaultSort() : bool;


    /**
     * @return bool
     */
    public function isExportable() : bool;


    /**
     * @return bool
     */
    public function isSelectable() : bool;


    /**
     * @return bool
     */
    public function isSortable() : bool;


    /**
     * @param bool $default_selected
     *
     * @return self
     */
    public function withDefaultSelected(bool $default_selected = true) : self;


    /**
     * @param bool $default_sort
     *
     * @return self
     */
    public function withDefaultSort(bool $default_sort = false) : self;


    /**
     * @param int $default_sort_direction
     *
     * @return self
     */
    public function withDefaultSortDirection(int $default_sort_direction = SortField::SORT_DIRECTION_UP) : self;


    /**
     * @param bool $exportable
     *
     * @return self
     */
    public function withExportable(bool $exportable = true) : self;


    /**
     * @param Formatter $formatter
     *
     * @return self
     */
    public function withFormatter(Formatter $formatter) : self;


    /**
     * @param string $key
     *
     * @return self
     */
    public function withKey(string $key) : self;


    /**
     * @param bool $selectable
     *
     * @return self
     */
    public function withSelectable(bool $selectable = true) : self;


    /**
     * @param bool $sortable
     *
     * @return self
     */
    public function withSortable(bool $sortable = true) : self;


    /**
     * @param string $title
     *
     * @return self
     */
    public function withTitle(string $title) : self;
}
