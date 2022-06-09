<?php

namespace srag\DataTableUI\OpencastObject\Implementation\Settings\Storage;

use srag\DataTableUI\OpencastObject\Component\Column\Column;
use srag\DataTableUI\OpencastObject\Component\Settings\Settings;
use srag\DataTableUI\OpencastObject\Component\Settings\Sort\SortField;
use srag\DataTableUI\OpencastObject\Component\Settings\Storage\SettingsStorage;
use srag\DataTableUI\OpencastObject\Component\Table;
use srag\DataTableUI\OpencastObject\Implementation\Utils\DataTableUITrait;
use srag\DIC\OpencastObject\DICTrait;

/**
 * Class AbstractSettingsStorage
 *
 * @package srag\DataTableUI\OpencastObject\Implementation\Settings\Storage
 */
abstract class AbstractSettingsStorage implements SettingsStorage
{

    use DICTrait;
    use DataTableUITrait;

    /**
     * AbstractSettingsStorage constructor
     */
    public function __construct()
    {

    }


    /**
     * @inheritDoc
     */
    public function handleDefaultSettings(Settings $settings, Table $component) : Settings
    {
        if (!$settings->isFilterSet() && empty($settings->getSortFields())) {
            $settings = $settings->withSortFields(array_map(function (Column $column) use ($component) : SortField {
                return self::dataTableUI()->settings()->sort()->sortField($column->getKey(), $column->getDefaultSortDirection());
            }, array_filter($component->getColumns(), function (Column $column) : bool {
                return ($column->isSortable() && $column->isDefaultSort());
            })));
        }

        if (!$settings->isFilterSet() && empty($settings->getSelectedColumns())) {
            $settings = $settings->withSelectedColumns(array_map(function (Column $column) : string {
                return $column->getKey();
            }, array_filter($component->getColumns(), function (Column $column) : bool {
                return ($column->isSelectable() && $column->isDefaultSelected());
            })));
        }

        return $settings;
    }
}
