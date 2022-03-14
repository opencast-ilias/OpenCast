<?php

namespace srag\DataTableUI\OpenCast\Implementation\Format\Browser;

use ILIAS\UI\Component\Component;
use ILIAS\UI\Component\Input\Container\Filter\Standard as FilterStandard;
use ilUtil;
use LogicException;
use srag\CustomInputGUIs\OpenCast\FormBuilder\FormBuilder;
use srag\CustomInputGUIs\OpenCast\Template\Template;
use srag\DataTableUI\OpenCast\Component\Column\Column;
use srag\DataTableUI\OpenCast\Component\Data\Data;
use srag\DataTableUI\OpenCast\Component\Data\Row\RowData;
use srag\DataTableUI\OpenCast\Component\Format\Browser\BrowserFormat;
use srag\DataTableUI\OpenCast\Component\Format\Format;
use srag\DataTableUI\OpenCast\Component\Settings\Settings;
use srag\DataTableUI\OpenCast\Component\Settings\Sort\SortField;
use srag\DataTableUI\OpenCast\Component\Settings\Storage\SettingsStorage;
use srag\DataTableUI\OpenCast\Component\Table;
use srag\DataTableUI\OpenCast\Implementation\Format\HtmlFormat;
use Throwable;

/**
 * Class DefaultBrowserFormat
 *
 * @package srag\DataTableUI\OpenCast\Implementation\Format\Browser
 */
class DefaultBrowserFormat extends HtmlFormat implements BrowserFormat
{

    /**
     * @var FilterStandard|FormBuilder|null
     */
    protected $filter_form = null;


    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @inheritDoc
     */
    public function actionParameter(string $key, string $table_id) : string
    {
        return $key . "_" . $table_id;
    }


    /**
     * @inheritDoc
     */
    public function deliverDownload(string $data, Table $component) : void
    {
        throw new LogicException("Seperate deliver download browser format not possible!");
    }


    /**
     * @inheritDoc
     */
    public function getActionRowId(string $table_id) : string
    {
        return strval(filter_input(INPUT_GET, $this->actionParameter(Table::ACTION_GET_VAR, $table_id)));
    }


    /**
     * @inheritDoc
     */
    public function getActionUrlWithParams(string $action_url, array $params, string $table_id) : string
    {
        foreach ($params as $key => $value) {
            $action_url = ilUtil::appendUrlParameterString($action_url, $this->actionParameter($key, $table_id) . "=" . $value);
        }

        return $action_url;
    }


    /**
     * @inheritDoc
     */
    public function getFormatId() : string
    {
        return self::FORMAT_BROWSER;
    }


    /**
     * @param Table $component
     *
     * @return string|null
     */
    public function getInputFormatId(Table $component) : ?string
    {
        return filter_input(INPUT_GET, $this->actionParameter(SettingsStorage::VAR_EXPORT_FORMAT_ID, $component->getTableId()));
    }


    /**
     * @inheritDoc
     */
    public function getMultipleActionRowIds(string $table_id) : array
    {
        return (filter_input(INPUT_POST, $this->actionParameter(Table::MULTIPLE_SELECT_POST_VAR, $table_id), FILTER_DEFAULT, FILTER_FORCE_ARRAY)
            ?? []);
    }


    /**
     * @inheritDoc
     */
    public function getOutputType() : int
    {
        return self::OUTPUT_TYPE_PRINT;
    }


    /**
     * @inheritDoc
     */
    public function handleSettingsInput(Table $component, Settings $settings) : Settings
    {
        $sort_field = strval(filter_input(INPUT_GET, $this->actionParameter(SettingsStorage::VAR_SORT_FIELD, $component->getTableId())));
        $sort_field_direction = intval(filter_input(INPUT_GET, $this->actionParameter(SettingsStorage::VAR_SORT_FIELD_DIRECTION, $component->getTableId())));
        if ($this->validateColumnKey($component, $sort_field) && !empty($sort_field_direction)) {
            $settings = $settings->addSortField(self::dataTableUI()->settings()->sort()->sortField($sort_field, $sort_field_direction));

            $settings = $settings->withFilterSet(true);
        }

        $remove_sort_field = strval(filter_input(INPUT_GET, $this->actionParameter(SettingsStorage::VAR_REMOVE_SORT_FIELD, $component->getTableId())));
        if ($this->validateColumnKey($component, $remove_sort_field)) {
            $settings = $settings->removeSortField($remove_sort_field);

            $settings = $settings->withFilterSet(true);
        }

        $rows_count = intval(filter_input(INPUT_GET, $this->actionParameter(SettingsStorage::VAR_ROWS_COUNT, $component->getTableId())));
        if (!empty($rows_count)) {
            $settings = $settings->withRowsCount($rows_count);
            $settings = $settings->withCurrentPage(); // Reset current page on row change
        }

        $current_page = filter_input(INPUT_GET, $this->actionParameter(SettingsStorage::VAR_CURRENT_PAGE, $component->getTableId()));
        if ($current_page !== null) {
            $settings = $settings->withCurrentPage(intval($current_page));

            $settings = $settings->withFilterSet(true);
        }

        $select_column = strval(filter_input(INPUT_GET, $this->actionParameter(SettingsStorage::VAR_SELECT_COLUMN, $component->getTableId())));
        if ($this->validateColumnKey($component, $select_column)) {
            $settings = $settings->selectColumn($select_column);

            $settings = $settings->withFilterSet(true);
        }

        $deselect_column = strval(filter_input(INPUT_GET, $this->actionParameter(SettingsStorage::VAR_DESELECT_COLUMN, $component->getTableId())));
        if ($this->validateColumnKey($component, $deselect_column)) {
            $settings = $settings->deselectColumn($deselect_column);

            $settings = $settings->withFilterSet(true);
        }

        if (count($component->getFilterFields()) > 0) {
            $filter_field_values = boolval(filter_input(INPUT_GET, $this->actionParameter(SettingsStorage::VAR_FILTER_FIELD_VALUES, $component->getTableId())));
            if ($filter_field_values) {

                $this->initFilterForm($component, $settings);

                try {
                    $settings->withFilterFieldValues(self::dic()->uiService()->filter()->getData($this->filter_form) ?? []);

                    $this->filter_form = null;
                } catch (Throwable $ex) {

                }

                if (!empty(array_filter($settings->getFilterFieldValues()))) {
                    $settings = $settings->withFilterSet(true);

                    $settings = $settings->withCurrentPage(); // Reset current page on filter change
                }
            }

            $reset_filter_field_values = boolval(filter_input(INPUT_GET, $this->actionParameter(SettingsStorage::VAR_RESET_FILTER_FIELD_VALUES, $component->getTableId())));
            if ($reset_filter_field_values) {

                $settings = $settings->withFilterFieldValues([]);

                $settings = $settings->withCurrentPage(); // Reset current page on filter change

                $this->filter_form = null;
            }
        }

        return $settings;
    }


    /**
     * @inheritDoc
     */
    protected function getColumns(Table $component, Settings $settings) : array
    {
        return $this->getColumnsBase($component, $settings);
    }


    /**
     * @param Table    $component
     * @param Settings $settings
     *
     * @return Component
     */
    protected function getColumnsSelector(Table $component, Settings $settings) : Component
    {
        return self::dic()->ui()->factory()->dropdown()
            ->standard(array_map(function (Column $column) use ($component, $settings) : Component {
                return self::dic()->ui()->factory()->link()->standard(self::output()->getHTML([
                    str_replace(["<a ", "</a>"], ["<span ", "</span>"], self::output()->getHTML(self::dic()->ui()->factory()->symbol()->glyph()->add())),
                    self::dic()->ui()->factory()->legacy($column->getTitle())
                ]), $this->getActionUrlWithParams($component->getActionUrl(), [SettingsStorage::VAR_SELECT_COLUMN => $column->getKey()], $component->getTableId()));
            }, array_filter($component->getColumns(), function (Column $column) use ($settings) : bool {
                return ($column->isSelectable() && !in_array($column->getKey(), $settings->getSelectedColumns()));
            })))->withLabel($component->getPlugin()->translate("add_columns", Table::LANG_MODULE));
    }


    /**
     * @param Table $component
     *
     * @return Component
     */
    protected function getExportsSelector(Table $component) : Component
    {
        return self::dic()->ui()->factory()->dropdown()->standard(array_map(function (Format $format) use ($component) : Component {
            return self::dic()->ui()->factory()->link()->standard($format->getDisplayTitle($component),
                $this->getActionUrlWithParams($component->getActionUrl(), [SettingsStorage::VAR_EXPORT_FORMAT_ID => $format->getFormatId()], $component->getTableId()));
        }, $component->getFormats()))->withLabel($component->getPlugin()->translate("export", Table::LANG_MODULE));
    }


    /**
     * @param Table     $component
     * @param Settings  $settings
     * @param Data|null $data
     *
     * @return Component
     */
    protected function getPagesSelector(Table $component, Settings $settings, ?Data $data) : Component
    {
        return $settings->getPagination($data)
            ->withTargetURL($component->getActionUrl(), $this->actionParameter(SettingsStorage::VAR_CURRENT_PAGE, $component->getTableId()));
    }


    /**
     * @param Table    $component
     * @param Settings $settings
     *
     * @return Component
     */
    protected function getRowsPerPageSelector(Table $component, Settings $settings) : Component
    {
        return self::dic()->ui()->factory()->dropdown()
            ->standard(array_map(function (int $count) use ($component, $settings) : Component {
                if ($settings->getRowsCount() === $count) {
                    return self::dic()->ui()->factory()->legacy(self::output()->getHTML([
                        self::dic()->ui()->factory()->symbol()->glyph()->apply(),
                        self::dic()->ui()->factory()->legacy(strval($count))
                    ]));
                } else {
                    return self::dic()->ui()
                        ->factory()
                        ->link()
                        ->standard(strval($count), $this->getActionUrlWithParams($component->getActionUrl(), [SettingsStorage::VAR_ROWS_COUNT => $count], $component->getTableId()));
                }
            }, Settings::ROWS_COUNT))->withLabel($component->getPlugin()
                ->translate("rows_per_page", Table::LANG_MODULE, [$settings->getRowsCount()]));
    }


    /**
     * @param Table     $component
     * @param Settings  $settings
     * @param Data|null $data
     */
    protected function handleActionsPanel(Table $component, Settings $settings, ?Data $data) : void
    {
        $this->tpl->setCurrentBlock("actions");

        $this->tpl->setVariable("ACTIONS", self::output()->getHTML(self::dic()->ui()->factory()->panel()->standard("", [
            $this->getPagesSelector($component, $settings, $data),
            $this->getColumnsSelector($component, $settings),
            $this->getRowsPerPageSelector($component, $settings),
            $this->getExportsSelector($component)
        ])));

        $this->tpl->parseCurrentBlock();
    }


    /**
     * @inheritDoc
     */
    protected function handleColumn(string $formatted_column, Table $component, Column $column, Settings $settings) : void
    {
        $deselect_button = self::dic()->ui()->factory()->legacy("");
        $sort_button = $formatted_column;
        $remove_sort_button = self::dic()->ui()->factory()->legacy("");

        if ($column->isSelectable()) {
            $deselect_button = self::dic()->ui()->factory()->symbol()->glyph()->remove($this->getActionUrlWithParams($component->getActionUrl(),
                [SettingsStorage::VAR_DESELECT_COLUMN => $column->getKey()],
                $component->getTableId()));
        }

        if ($column->isSortable()) {
            $sort_field = $settings->getSortField($column->getKey());

            if ($sort_field !== null) {
                if ($sort_field->getSortFieldDirection() === SortField::SORT_DIRECTION_DOWN) {
                    $sort_button = self::dic()->ui()->factory()->link()->standard(self::output()->getHTML([
                        self::dic()->ui()->factory()->legacy($sort_button),
                        self::dic()->ui()->factory()->symbol()->glyph()->sortDescending()
                    ]), $this->getActionUrlWithParams($component->getActionUrl(), [
                        SettingsStorage::VAR_SORT_FIELD           => $column->getKey(),
                        SettingsStorage::VAR_SORT_FIELD_DIRECTION => SortField::SORT_DIRECTION_UP
                    ], $component->getTableId()));
                } else {
                    $sort_button = self::dic()->ui()->factory()->link()->standard(self::output()->getHTML([
                        self::dic()->ui()->factory()->legacy($sort_button),
                        self::dic()->ui()->factory()->symbol()->glyph()->sortAscending()
                    ]), $this->getActionUrlWithParams($component->getActionUrl(), [
                        SettingsStorage::VAR_SORT_FIELD           => $column->getKey(),
                        SettingsStorage::VAR_SORT_FIELD_DIRECTION => SortField::SORT_DIRECTION_DOWN
                    ], $component->getTableId()));
                }

                $remove_sort_button = self::dic()->ui()->factory()->link()->standard($component->getPlugin()
                    ->translate("remove_sort", Table::LANG_MODULE),
                    $this->getActionUrlWithParams($component->getActionUrl(), [SettingsStorage::VAR_REMOVE_SORT_FIELD => $column->getKey()], $component->getTableId())); // TODO: Remove sort icon
            } else {
                $sort_button = self::dic()->ui()->factory()->link()->standard($sort_button, $this->getActionUrlWithParams($component->getActionUrl(), [
                    SettingsStorage::VAR_SORT_FIELD           => $column->getKey(),
                    SettingsStorage::VAR_SORT_FIELD_DIRECTION => SortField::SORT_DIRECTION_UP
                ], $component->getTableId()));
            }
        } else {
            $sort_button = self::dic()->ui()->factory()->legacy($sort_button);
        }

        $formatted_column = self::output()->getHTML([
            $deselect_button,
            $sort_button,
            $remove_sort_button
        ]);

        parent::handleColumn($formatted_column, $component, $column, $settings);
    }


    /**
     * @inheritDoc
     */
    protected function handleColumns(Table $component, array $columns, Settings $settings) : void
    {
        if (count($component->getMultipleActions()) > 0) {
            $this->tpl->setCurrentBlock("header");

            $this->tpl->setVariableEscaped("HEADER", "");

            $this->tpl->parseCurrentBlock();
        }

        parent::handleColumns($component, $columns, $settings);
    }


    /**
     * @param Table     $component
     * @param Settings  $settings
     * @param Data|null $data
     */
    protected function handleDisplayCount(Table $component, Settings $settings, ?Data $data) : void
    {
        $count = $component->getPlugin()->translate("count", Table::LANG_MODULE, [
            ($data !== null && $data->getDataCount() > 0 ? ($settings->getOffset() + 1) : 0),
            ($data === null ? 0 : $data->getMaxCount())
        ]);

        $this->tpl->setCurrentBlock("count_top");
        $this->tpl->setVariableEscaped("COUNT_TOP", $count);
        $this->tpl->parseCurrentBlock();

        $this->tpl->setCurrentBlock("count_bottom");
        $this->tpl->setVariableEscaped("COUNT_BOTTOM", $count);
        $this->tpl->parseCurrentBlock();
    }


    /**
     * @param Table    $component
     * @param Settings $settings
     */
    protected function handleFilterForm(Table $component, Settings $settings) : void
    {
        if (empty($component->getFilterFields())) {
            return;
        }

        $this->initFilterForm($component, $settings);

        $filter_form = self::output()->getHTML($this->filter_form);

        $this->tpl->setCurrentBlock("filter");

        $this->tpl->setVariable("FILTER_FORM", $filter_form);

        $this->tpl->parseCurrentBlock();
    }


    /**
     * @param Table $component
     */
    protected function handleMultipleActions(Table $component) : void
    {
        if (empty($component->getMultipleActions())) {
            return;
        }

        $tpl_checkbox = new Template(__DIR__ . "/../../../../templates/tpl.datatableui_row.html", true, false);

        $tpl_checkbox->setCurrentBlock("row_checkbox");

        $multiple_actions = [
            self::dic()->ui()->factory()->legacy(self::output()->getHTML($tpl_checkbox)),
            self::dic()->ui()->factory()->legacy($component->getPlugin()->translate("select_all", Table::LANG_MODULE)),
            self::dic()->ui()->factory()->dropdown()->standard(array_map(function (string $title, string $action) : Component {
                return self::dic()->ui()->factory()->link()->standard($title, $action);
            }, array_keys($component->getMultipleActions()), $component->getMultipleActions()))->withLabel($component->getPlugin()
                ->translate("multiple_actions", Table::LANG_MODULE))
        ];

        $this->tpl->setCurrentBlock("multiple_actions_top");
        $this->tpl->setVariable("MULTIPLE_ACTIONS_TOP", self::output()->getHTML($multiple_actions));
        $this->tpl->parseCurrentBlock();

        $this->tpl->setCurrentBlock("multiple_actions_bottom");
        $this->tpl->setVariable("MULTIPLE_ACTIONS_BOTTOM", self::output()->getHTML($multiple_actions));
        $this->tpl->parseCurrentBlock();
    }


    /**
     * @inheritDoc
     */
    protected function handleRowTemplate(Table $component, RowData $row) : void
    {
        parent::handleRowTemplate($component, $row);

        if (count($component->getMultipleActions()) > 0) {
            $this->tpl->setCurrentBlock("row_checkbox");

            $this->tpl->setVariableEscaped("POST_VAR", $this->actionParameter(Table::MULTIPLE_SELECT_POST_VAR, $component->getTableId()) . "[]");

            $this->tpl->setVariableEscaped("ROW_ID", $row->getRowId());

            $this->tpl->parseCurrentBlock();
        }
    }


    /**
     * @param Table    $component
     * @param Settings $settings
     */
    protected function initFilterForm(Table $component, Settings $settings) : void
    {
        if ($this->filter_form === null) {
            $filter_fields = $component->getFilterFields();

            $this->filter_form = self::dic()->uiService()->filter()
                ->standard($component->getTableId(), $this->getActionUrlWithParams($component->getActionUrl(), [SettingsStorage::VAR_FILTER_FIELD_VALUES => true], $component->getTableId()),
                    $filter_fields,
                    array_fill(0, count($filter_fields), false),
                    true, true);
        }
    }


    /**
     * @inheritDoc
     */
    protected function initTemplate(Table $component, ?Data $data, Settings $settings) : void
    {
        parent::initTemplate($component, $data, $settings);

        $this->handleFilterForm($component, $settings);

        $this->handleActionsPanel($component, $settings, $data);

        $this->handleDisplayCount($component, $settings, $data);

        $this->handleMultipleActions($component);
    }


    /**
     * @param Table  $component
     * @param string $key
     *
     * @return bool
     */
    protected function validateColumnKey(Table $component, string $key) : bool
    {
        return (!empty($key)
            && !empty(array_filter($component->getColumns(), function (Column $column) use ($key) : bool {
                return ($column->getKey() === $key);
            })));
    }
}
