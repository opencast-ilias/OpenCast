<?php

use srag\DataTableUI\OpenCast\Component\Column\Column;
use srag\DataTableUI\OpenCast\Component\Data\Data;
use srag\DataTableUI\OpenCast\Component\Data\Row\RowData;
use srag\DataTableUI\OpenCast\Component\Format\Format;
use srag\DataTableUI\OpenCast\Component\Settings\Settings;
use srag\DataTableUI\OpenCast\Component\Settings\Sort\SortField;
use srag\DataTableUI\OpenCast\Component\Table;
use srag\DataTableUI\OpenCast\Implementation\Column\Formatter\DefaultFormatter;
use srag\DataTableUI\OpenCast\Implementation\Data\Fetcher\AbstractDataFetcher;
use srag\DataTableUI\OpenCast\Implementation\Utils\AbstractTableBuilder;
use srag\DIC\OpenCast\DICStatic;

/**
 * @return string
 */
function advanced() : string
{
    $table = new AdvancedExampleTableBuilder(new ilSystemStyleDocumentationGUI());

    return DICStatic::output()->getHTML($table);
}

/**
 * Class AdvancedExampleDataFetcher
 */
class AdvancedExampleDataFetcher extends AbstractDataFetcher
{

    /**
     * @var string
     */
    protected $action_url;


    /**
     * @inheritDoc
     *
     * @param string $action_url
     */
    public function __construct(string $action_url)
    {
        $this->action_url = $action_url;

        parent::__construct();
    }


    /**
     * @inheritDoc
     */
    public function fetchData(Settings $settings) : Data
    {
        $sql = 'SELECT *' . $this->getQuery($settings);

        $result = self::dic()->database()->query($sql);

        $rows = [];
        while (!empty($row = self::dic()->database()->fetchObject($result))) {
            $row->type_icon = $row->type;

            $row->title_link = ilLink::_getLink(current(ilObject::_getAllReferences($row->obj_id)));

            $row->actions = [
                self::dic()->ui()->factory()->link()->standard("Action", $this->action_url)
            ];

            $rows[] = self::dataTableUI()->data()->row()->property(strval($row->obj_id), $row);
        }

        $sql = 'SELECT COUNT(obj_id) AS count' . $this->getQuery($settings, true);

        $result = self::dic()->database()->query($sql);

        $max_count = intval($result->fetchAssoc()["count"]);

        return self::dataTableUI()->data()->data($rows, $max_count);
    }


    /**
     * @param Settings $settings
     * @param bool     $max_count
     *
     * @return string
     */
    protected function getQuery(Settings $settings, bool $max_count = false) : string
    {
        $sql = ' FROM object_data';

        $field_values = array_filter($settings->getFilterFieldValues());

        if (!empty($field_values)) {
            $sql .= ' WHERE ' . implode(' AND ', array_map(function (string $key, string $value) : string {
                    return self::dic()->database()->like($key, ilDBConstants::T_TEXT, '%' . $value . '%');
                }, array_keys($field_values), $field_values));
        }

        if (!$max_count) {
            if (!empty($settings->getSortFields())) {
                $sql .= ' ORDER BY ' . implode(", ", array_map(function (SortField $sort_field) : string {
                        return self::dic()->database()->quoteIdentifier($sort_field->getSortField()) . ' ' . ($sort_field->getSortFieldDirection()
                            === SortField::SORT_DIRECTION_DOWN ? 'DESC' : 'ASC');
                    }, $settings->getSortFields()));
            }

            if (!empty($settings->getOffset()) && !empty($settings->getRowsCount())) {
                self::dic()->database()->setLimit($settings->getRowsCount(), $settings->getOffset());
            }
        }

        return $sql;
    }
}

/**
 * Class AdvancedExampleFormatter
 */
class AdvancedExampleFormatter extends DefaultFormatter
{

    /**
     * @inheritDoc
     */
    public function formatRowCell(Format $format, $value, Column $column, RowData $row, string $table_id) : string
    {
        $type = parent::formatRowCell($format, $value, $column, $row, $table_id);

        switch ($format->getFormatId()) {
            case Format::FORMAT_BROWSER:
            case Format::FORMAT_PDF:
            case Format::FORMAT_HTML:
                return self::output()->getHTML([
                    self::dic()->ui()->factory()->symbol()->icon()->custom(ilObject::_getIcon($row->getRowId(), "small"), $type),
                    self::dic()->ui()->factory()->legacy($type)
                ]);

            default:
                return $type;
        }
    }
}

/**
 * Class AdvancedExampleTableBuilder
 */
class AdvancedExampleTableBuilder extends AbstractTableBuilder
{

    /**
     * @inheritDoc
     *
     * @param ilSystemStyleDocumentationGUI $parent
     */
    public function __construct(ilSystemStyleDocumentationGUI $parent)
    {
        parent::__construct($parent);
    }


    /**
     * @inheritDoc
     */
    public function render() : string
    {
        $info_text = "";

        $action_row_id = $this->getTable()->getBrowserFormat()->getActionRowId($this->getTable()->getTableId());
        if ($action_row_id !== "") {
            $info_text = $info_text = self::dic()->ui()->factory()->messageBox()->info("Row id: " . $action_row_id);
        }

        $mutliple_action_row_ids = $this->getTable()->getBrowserFormat()->getMultipleActionRowIds($this->getTable()->getTableId());
        if (!empty($mutliple_action_row_ids)) {
            $info_text = self::dic()->ui()->factory()->messageBox()->info("Row ids: " . implode(", ", $mutliple_action_row_ids));
        }

        return self::output()->getHTML([$info_text, parent::render()]);
    }


    /**
     * @inheritDoc
     */
    protected function buildTable() : Table
    {
        self::dic()->ctrl()->saveParameter($this->parent, "node_id");
        $action_url = self::dic()->ctrl()->getLinkTarget($this->parent, "", "", false, false);

        $table = self::dataTableUI()->table("example_datatableui_advanced", $action_url, "Advanced example data table", [
            self::dataTableUI()->column()->column("obj_id", "Id")->withDefaultSelected(false),
            self::dataTableUI()->column()->column("title", "Title")->withFormatter(self::dataTableUI()->column()->formatter()->link())->withDefaultSort(true),
            self::dataTableUI()->column()->column("type", "Type")->withFormatter(self::dataTableUI()->column()->formatter()->languageVariable("obj")),
            self::dataTableUI()->column()->column("type_icon", "Type icon")->withFormatter(new AdvancedExampleFormatter()),
            self::dataTableUI()->column()->column("description", "Description")->withDefaultSelected(false)->withSortable(false),
            self::dataTableUI()->column()->column("actions", "Actions")->withFormatter(self::dataTableUI()->column()->formatter()->actions()->actionsDropdown())
        ], new AdvancedExampleDataFetcher($action_url)
        )->withFilterFields([
            "title" => self::dic()->ui()->factory()->input()->field()->text("Title"),
            "type"  => self::dic()->ui()->factory()->input()->field()->text("Type")
        ])->withFormats([
            self::dataTableUI()->format()->csv(),
            self::dataTableUI()->format()->excel(),
            self::dataTableUI()->format()->pdf(),
            self::dataTableUI()->format()->html()
        ])->withMultipleActions([
            "Action" => $action_url
        ]);

        return $table;
    }
}
