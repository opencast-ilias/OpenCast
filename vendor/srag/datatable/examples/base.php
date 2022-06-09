<?php

use srag\DataTableUI\OpencastObject\Component\Table;
use srag\DataTableUI\OpencastObject\Implementation\Utils\AbstractTableBuilder;
use srag\DIC\OpencastObject\DICStatic;

/**
 * @return string
 */
function base() : string
{
    $table = new BaseTableBuilder(new ilSystemStyleDocumentationGUI());

    return DICStatic::output()->getHTML($table);
}

/**
 * Class BaseTableBuilder
 */
class BaseTableBuilder extends AbstractTableBuilder
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
    protected function buildTable() : Table
    {
        self::dic()->ctrl()->saveParameter($this->parent, "node_id");
        $action_url = self::dic()->ctrl()->getLinkTarget($this->parent, "", "", false, false);

        $data = array_map(function (int $index) : stdClass {
            return (object) [
                "column1" => $index,
                "column2" => "text $index",
                "column3" => ($index % 2 === 0 ? "true" : "false")
            ];
        }, range(0, 25));
        $table = self::dataTableUI()->table("example_datatableui_base", $action_url, "Example data table", [
            self::dataTableUI()->column()->column("column1", "Column 1"),
            self::dataTableUI()->column()->column("column2", "Column 2"),
            self::dataTableUI()->column()->column("column3", "Column 3")
        ], self::dataTableUI()->data()->fetcher()->staticData($data, "column1"));

        return $table;
    }
}
