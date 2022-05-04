<?php

namespace srag\DataTableUI\OpencastObject\Implementation\Column\Formatter;

use srag\DataTableUI\OpencastObject\Component\Column\Column;
use srag\DataTableUI\OpencastObject\Component\Data\Row\RowData;
use srag\DataTableUI\OpencastObject\Component\Format\Format;

/**
 * Class LinkFormatter
 *
 * @package srag\DataTableUI\OpencastObject\Implementation\Column\Formatter
 */
class LinkFormatter extends DefaultFormatter
{

    /**
     * @inheritDoc
     */
    public function formatRowCell(Format $format, $title, Column $column, RowData $row, string $table_id) : string
    {
        $link = $row($column->getKey() . "_link");

        if (empty($title) || empty($link)) {
            return $title;
        }

        return self::output()->getHTML(self::dic()->ui()->factory()->link()->standard($title, $link));
    }
}
