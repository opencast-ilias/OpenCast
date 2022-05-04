<?php

namespace srag\DataTableUI\OpencastObject\Implementation\Column\Formatter;

use ilDatePresentation;
use ilDateTime;
use srag\DataTableUI\OpencastObject\Component\Column\Column;
use srag\DataTableUI\OpencastObject\Component\Data\Row\RowData;
use srag\DataTableUI\OpencastObject\Component\Format\Format;

/**
 * Class DateFormatter
 *
 * @package srag\DataTableUI\OpencastObject\Implementation\Column\Formatter
 */
class DateFormatter extends DefaultFormatter
{

    /**
     * @inheritDoc
     */
    public function formatRowCell(Format $format, $date, Column $column, RowData $row, string $table_id) : string
    {
        if (empty($date)) {
            return "";
        }

        if (!$date instanceof ilDateTime) {
            if (is_numeric($date)) {
                $date = new ilDateTime($date, IL_CAL_UNIX);
            } else {
                $date = new ilDateTime($date, IL_CAL_DATETIME);
            }
        }

        switch ($format->getFormatId()) {
            case Format::FORMAT_BROWSER:
            case Format::FORMAT_PDF:
            case Format::FORMAT_HTML:
                return ilDatePresentation::formatDate($date);

            case Format::FORMAT_EXCEL:
                return $date->get(IL_CAL_DATETIME);

            case Format::FORMAT_CSV:
            default:
                return strval($date->getUnixTime());
        }
    }
}
