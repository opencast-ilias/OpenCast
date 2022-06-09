<?php

namespace srag\DataTableUI\OpencastObject\Implementation\Column\Formatter;

use srag\DataTableUI\OpencastObject\Component\Column\Column;
use srag\DataTableUI\OpencastObject\Component\Data\Row\RowData;
use srag\DataTableUI\OpencastObject\Component\Format\Format;

/**
 * Class ImageFormatter
 *
 * @package srag\DataTableUI\OpencastObject\Implementation\Column\Formatter
 */
class ImageFormatter extends DefaultFormatter
{

    /**
     * @inheritDoc
     */
    public function formatRowCell(Format $format, $image, Column $column, RowData $row, string $table_id) : string
    {
        if (!empty($image)) {
            return self::output()->getHTML(self::dic()->ui()->factory()->image()->responsive($image, ""));
        } else {
            return "";
        }
    }
}
