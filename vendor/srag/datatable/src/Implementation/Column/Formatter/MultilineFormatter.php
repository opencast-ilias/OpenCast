<?php

namespace srag\DataTableUI\OpencastObject\Implementation\Column\Formatter;

use srag\DataTableUI\OpencastObject\Component\Column\Column;
use srag\DataTableUI\OpencastObject\Component\Data\Row\RowData;
use srag\DataTableUI\OpencastObject\Component\Format\Format;

/**
 * Class MultilineFormatter
 *
 * @package srag\DataTableUI\OpencastObject\Implementation\Column\Formatter
 */
class MultilineFormatter extends DefaultFormatter
{

    /**
     * @inheritDoc
     */
    public function formatRowCell(Format $format, $value, Column $column, RowData $row, string $table_id) : string
    {
        return nl2br(implode("\n", array_map("htmlspecialchars", explode("\n", strval($value)))), false);
    }
}
