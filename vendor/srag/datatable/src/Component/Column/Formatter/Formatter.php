<?php

namespace srag\DataTableUI\OpenCast\Component\Column\Formatter;

use srag\DataTableUI\OpenCast\Component\Column\Column;
use srag\DataTableUI\OpenCast\Component\Data\Row\RowData;
use srag\DataTableUI\OpenCast\Component\Format\Format;

/**
 * Interface Formatter
 *
 * @package srag\DataTableUI\OpenCast\Component\Column\Formatter
 */
interface Formatter
{

    /**
     * @param Format $format
     * @param Column $column
     * @param string $table_id
     *
     * @return string
     */
    public function formatHeaderCell(Format $format, Column $column, string $table_id) : string;


    /**
     * @param Format  $format
     * @param mixed   $value
     * @param Column  $column
     * @param RowData $row
     * @param string  $table_id
     *
     * @return string
     */
    public function formatRowCell(Format $format, $value, Column $column, RowData $row, string $table_id) : string;
}
