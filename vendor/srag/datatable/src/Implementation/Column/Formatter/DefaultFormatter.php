<?php

namespace srag\DataTableUI\OpenCast\Implementation\Column\Formatter;

use ilExcel;
use srag\DataTableUI\OpenCast\Component\Column\Column;
use srag\DataTableUI\OpenCast\Component\Data\Row\RowData;
use srag\DataTableUI\OpenCast\Component\Format\Format;

/**
 * Class DefaultFormatter
 *
 * @package srag\DataTableUI\OpenCast\Implementation\Column\Formatter
 */
class DefaultFormatter extends AbstractFormatter
{

    /**
     * @inheritDoc
     */
    public function formatHeaderCell(Format $format, Column $column, string $table_id) : string
    {
        $title = $column->getTitle();

        switch ($format->getFormatId()) {
            case Format::FORMAT_CSV:
                return $title;

            case Format::FORMAT_EXCEL:
                /**
                 * @var ilExcel $tpl
                 */ $tpl = $format->getTemplate()->tpl;
                $cord = $tpl->getColumnCoord($format->getTemplate()->current_col) . $format->getTemplate()->current_row;
                $tpl->setBold($cord . ":" . $cord);

                return $title;

            case Format::FORMAT_PDF:
                return "<b>" . htmlspecialchars($title) . "</b>";

            default:
                return htmlspecialchars($title);
        }
    }


    /**
     * @inheritDoc
     */
    public function formatRowCell(Format $format, $value, Column $column, RowData $row, string $table_id) : string
    {
        $value = strval($value);

        switch ($format->getFormatId()) {
            case Format::FORMAT_BROWSER:
            case Format::FORMAT_HTML:
            case Format::FORMAT_PDF:
                if (empty($value)) {
                    return "&nbsp;";
                }

                return htmlspecialchars($value);

            default:
                return $value;
        }
    }
}
