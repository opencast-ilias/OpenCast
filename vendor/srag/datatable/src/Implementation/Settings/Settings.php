<?php

namespace srag\DataTableUI\OpencastObject\Implementation\Settings;

use Closure;
use ILIAS\UI\Component\ViewControl\Pagination as PaginationInterface;
use ILIAS\UI\Implementation\Component\ComponentHelper;
use ILIAS\UI\Implementation\Component\ViewControl\Pagination;
use srag\DataTableUI\OpencastObject\Component\Data\Data;
use srag\DataTableUI\OpencastObject\Component\Settings\Settings as SettingsInterface;
use srag\DataTableUI\OpencastObject\Component\Settings\Sort\SortField;
use srag\DataTableUI\OpencastObject\Implementation\Utils\DataTableUITrait;
use srag\DIC\OpencastObject\DICTrait;

/**
 * Class Settings
 *
 * @package srag\DataTableUI\OpencastObject\Implementation\Settings
 */
class Settings implements SettingsInterface
{

    use ComponentHelper;
    use DICTrait;
    use DataTableUITrait;

    /**
     * @var mixed[]
     */
    protected $filter_field_values = [];
    /**
     * @var bool
     */
    protected $filter_set = false;
    /**
     * @var PaginationInterface
     */
    protected $pagination;
    /**
     * @var string[]
     */
    protected $selected_columns = [];
    /**
     * @var SortField[]
     */
    protected $sort_fields = [];


    /**
     * Settings constructor
     *
     * @param PaginationInterface $pagination
     */
    public function __construct(PaginationInterface $pagination)
    {
        $this->pagination = $pagination->withPageSize(self::DEFAULT_ROWS_COUNT);
    }


    /**
     * @inheritDoc
     */
    public function addSortField(SortField $sort_field) : SettingsInterface
    {
        $clone = clone $this;

        if ($this->getSortField($sort_field->getSortField()) !== null) {
            $clone->sort_fields = array_reduce($clone->sort_fields, function (array $sort_fields, SortField $sort_field_) use ($sort_field) : array {
                if ($sort_field_->getSortField() === $sort_field->getSortField()) {
                    $sort_field_ = $sort_field;
                }

                $sort_fields[] = $sort_field_;

                return $sort_fields;
            }, []);
        } else {
            $clone->sort_fields[] = $sort_field;
        }

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function deselectColumn(string $selected_column) : SettingsInterface
    {
        $clone = clone $this;

        $clone->selected_columns = array_values(array_filter($clone->selected_columns, function (string $selected_column_) use ($selected_column) : bool {
            return ($selected_column_ !== $selected_column);
        }));

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function getCurrentPage() : int
    {
        return $this->pagination->getCurrentPage();
    }


    /**
     * @inheritDoc
     */
    public function getFilterFieldValue(string $key)
    {
        return $this->filter_field_values[$key] ?? null;
    }


    /**
     * @inheritDoc
     */
    public function getFilterFieldValues() : array
    {
        return $this->filter_field_values;
    }


    /**
     * @inheritDoc
     */
    public function getOffset() : int
    {
        if (self::version()->is7()) {
            // TODO: Start must be a positive number (or 0)
            //return $this->pagination->getRange()->getStart();

            return Closure::bind(function () : int {
                return $this->getOffset();
            }, $this->pagination, Pagination::class)();
        } else {
            return $this->pagination->getOffset();
        }
    }


    /**
     * @inheritDoc
     *
     * @internal
     */
    public function getPagination(?Data $data) : PaginationInterface
    {
        return $this->pagination->withTotalEntries($data === null ? 0 : $data->getMaxCount());
    }


    /**
     * @inheritDoc
     */
    public function getRowsCount() : int
    {
        return $this->pagination->getPageSize();
    }


    /**
     * @inheritDoc
     */
    public function getSelectedColumns() : array
    {
        return $this->selected_columns;
    }


    /**
     * @inheritDoc
     */
    public function getSortField(string $sort_field) : ?SortField
    {
        $sort_field = current(array_filter($this->sort_fields, function (SortField $sort_field_) use ($sort_field) : bool {
            return ($sort_field_->getSortField() === $sort_field);
        }));

        if ($sort_field !== false) {
            return $sort_field;
        } else {
            return null;
        }
    }


    /**
     * @inheritDoc
     */
    public function getSortFields() : array
    {
        return $this->sort_fields;
    }


    /**
     * @inheritDoc
     */
    public function isFilterSet() : bool
    {
        return $this->filter_set;
    }


    /**
     * @inheritDoc
     */
    public function removeSortField(string $sort_field) : SettingsInterface
    {
        $clone = clone $this;

        $clone->sort_fields = array_values(array_filter($clone->sort_fields, function (SortField $sort_field_) use ($sort_field) : bool {
            return ($sort_field_->getSortField() !== $sort_field);
        }));

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function selectColumn(string $selected_column) : SettingsInterface
    {
        $clone = clone $this;

        if (!in_array($selected_column, $clone->selected_columns)) {
            $clone->selected_columns[] = $selected_column;
        }

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withCurrentPage(int $current_page = 0) : SettingsInterface
    {
        $clone = clone $this;

        $clone->pagination = $clone->pagination->withCurrentPage($current_page);

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withFilterFieldValues(array $filter_field_values) : SettingsInterface
    {
        $clone = clone $this;

        $clone->filter_field_values = $filter_field_values;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withFilterSet(bool $filter_set = false) : SettingsInterface
    {
        $clone = clone $this;

        $clone->filter_set = $filter_set;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withRowsCount(int $rows_count = self::DEFAULT_ROWS_COUNT) : SettingsInterface
    {
        $clone = clone $this;

        $clone->pagination = $clone->pagination->withPageSize($rows_count);

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withSelectedColumns(array $selected_columns) : SettingsInterface
    {
        $clone = clone $this;

        $clone->selected_columns = $selected_columns;

        return $clone;
    }


    /**
     * @inheritDoc
     */
    public function withSortFields(array $sort_fields) : SettingsInterface
    {
        $classes = [SortField::class];
        $this->checkArgListElements("sort_fields", $sort_fields, $classes);

        $clone = clone $this;

        $clone->sort_fields = $sort_fields;

        return $clone;
    }
}
