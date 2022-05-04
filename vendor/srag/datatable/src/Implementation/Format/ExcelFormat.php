<?php

namespace srag\DataTableUI\OpencastObject\Implementation\Format;

use ilExcel;
use srag\DataTableUI\OpencastObject\Component\Column\Column;
use srag\DataTableUI\OpencastObject\Component\Data\Data;
use srag\DataTableUI\OpencastObject\Component\Data\Row\RowData;
use srag\DataTableUI\OpencastObject\Component\Settings\Settings;
use srag\DataTableUI\OpencastObject\Component\Table;

/**
 * Class ExcelFormat
 *
 * @package srag\DataTableUI\OpencastObject\Implementation\Format
 */
class ExcelFormat extends AbstractFormat
{

    /**
     * @var int
     */
    protected $current_col = 0;
    /**
     * @var int
     */
    protected $current_row = 1;
    /**
     * @var ilExcel
     */
    protected $tpl;


    /**
     * @inheritDoc
     */
    public function getFormatId() : string
    {
        return self::FORMAT_EXCEL;
    }


    /**
     * @inheritDoc
     */
    public function getTemplate() : object
    {
        return (object) [
            "tpl"         => $this->tpl,
            "current_row" => $this->current_row,
            "current_col" => $this->current_col
        ];
    }


    /**
     * @inheritDoc
     */
    protected function getFileExtension() : string
    {
        return "xlsx";
    }


    /**
     * @inheritDoc
     */
    protected function handleColumn(string $formatted_column, Table $component, Column $column, Settings $settings) : void
    {
        $this->tpl->setCell($this->current_row, $this->current_col, $formatted_column);

        $this->current_col++;
    }


    /**
     * @inheritDoc
     */
    protected function handleColumns(Table $component, array $columns, Settings $settings) : void
    {
        $this->current_col = 0;

        parent::handleColumns($component, $columns, $settings);

        $this->current_row++;
    }


    /**
     * @inheritDoc
     */
    protected function handleRow(Table $component, array $columns, RowData $row) : void
    {
        $this->current_col = 0;

        parent::handleRow($component, $columns, $row);

        $this->current_row++;
    }


    /**
     * @inheritDoc
     */
    protected function handleRowColumn(string $formatted_row_column) : void
    {
        $this->tpl->setCell($this->current_row, $this->current_col, $formatted_row_column);

        $this->current_col++;
    }


    /**
     * @inheritDoc
     */
    protected function initTemplate(Table $component, ?Data $data, Settings $settings) : void
    {
        $this->tpl = new ilExcel();

        $this->tpl->addSheet($component->getTitle());
    }


    /**
     * @inheritDoc
     */
    protected function renderTemplate(Table $component) : string
    {
        $tmp_file = $this->tpl->writeToTmpFile();

        $data = file_get_contents($tmp_file);

        unlink($tmp_file);

        return $data;
    }
}
