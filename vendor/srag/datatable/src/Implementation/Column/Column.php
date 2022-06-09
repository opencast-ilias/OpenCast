<?php

namespace srag\DataTableUI\OpencastObject\Implementation\Column;

use srag\DataTableUI\OpencastObject\Component\Column\Column as ColumnInterface;
use srag\DataTableUI\OpencastObject\Component\Column\Formatter\Actions\ActionsFormatter;
use srag\DataTableUI\OpencastObject\Component\Column\Formatter\Formatter;
use srag\DataTableUI\OpencastObject\Component\Settings\Sort\SortField;
use srag\DataTableUI\OpencastObject\Implementation\Utils\DataTableUITrait;
use srag\DIC\OpencastObject\DICTrait;

/**
 * Class Column
 *
 * @package srag\DataTableUI\OpencastObject\Implementation\Column
 */
class Column implements ColumnInterface
{

    use DICTrait;
    use DataTableUITrait;

    /**
     * @var bool
     */
    protected $default_selected = true;
    /**
     * @var bool
     */
    protected $default_sort = false;
    /**
     * @var int
     */
    protected $default_sort_direction = SortField::SORT_DIRECTION_UP;
    /**
     * @var bool
     */
    protected $exportable = true;
    /**
     * @var Formatter
     */
    protected $formatter;
    /**
     * @var string
     */
    protected $key = "";
    /**
     * @var bool
     */
    protected $selectable = true;
    /**
     * @var bool
     */
    protected $sortable = true;
    /**
     * @var string
     */
    protected $title = "";


    /**
     * @inheritDoc
     */
    public function __construct(string $key, string $title)
    {
        $this->key = $key;

        $this->title = $title;
    }


    /**
     * @inheritDoc
     */
    public function getDefaultSortDirection() : int
    {
        return $this->default_sort_direction;
    }


    /**
     * @inheritDoc
     */
    public function getFormatter() : Formatter
    {
        if ($this->formatter === null) {
            $this->formatter = self::dataTableUI()->column()->formatter()->default();
        }

        return $this->formatter;
    }


    /**
     * @inheritDoc
     */
    public function getKey() : string
    {
        return $this->key;
    }


    /**
     * @inheritDoc
     */
    public function getTitle() : string
    {
        return $this->title;
    }


    /**
     * @inheritDoc
     */
    public function isDefaultSelected() : bool
    {
        return $this->default_selected;
    }


    /**
     * @inheritDoc
     */
    public function isDefaultSort() : bool
    {
        return $this->default_sort;
    }


    /**
     * @inheritDoc
     */
    public function isExportable() : bool
    {
        return $this->exportable;
    }


    /**
     * @inheritDoc
     */
    public function isSelectable() : bool
    {
        return $this->selectable;
    }


    /**
     * @inheritDoc
     */
    public function isSortable() : bool
    {
        return $this->sortable;
    }


    /**
     * @inheritDoc
     */
    public function withDefaultSelected(bool $default_selected = true) : ColumnInterface
    {
        $clone = clone $this;

        $clone->default_selected = $default_selected;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withDefaultSort(bool $default_sort = false) : ColumnInterface
    {
        $clone = clone $this;

        $clone->default_sort = $default_sort;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withDefaultSortDirection(int $default_sort_direction = SortField::SORT_DIRECTION_UP) : ColumnInterface
    {
        $clone = clone $this;

        $clone->default_sort_direction = $default_sort_direction;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withExportable(bool $exportable = true) : ColumnInterface
    {
        $clone = clone $this;

        $clone->exportable = $exportable;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withFormatter(Formatter $formatter) : ColumnInterface
    {
        $clone = clone $this;

        $clone->formatter = $formatter;

        if ($clone->formatter instanceof ActionsFormatter) {
            $clone->sortable = false;
            $clone->selectable = false;
            $clone->exportable = false;
        }

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withKey(string $key) : ColumnInterface
    {
        $clone = clone $this;

        $clone->key = $key;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withSelectable(bool $selectable = true) : ColumnInterface
    {
        $clone = clone $this;

        $clone->selectable = $selectable;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withSortable(bool $sortable = true) : ColumnInterface
    {
        $clone = clone $this;

        $clone->sortable = $sortable;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withTitle(string $title) : ColumnInterface
    {
        $clone = clone $this;

        $clone->title = $title;

        return $clone;
    }
}
