<?php

namespace srag\DataTableUI\OpencastObject\Implementation\Column\Formatter;

use ilUtil;
use srag\DataTableUI\OpencastObject\Component\Column\Column;
use srag\DataTableUI\OpencastObject\Component\Data\Row\RowData;
use srag\DataTableUI\OpencastObject\Component\Format\Format;

/**
 * Class CheckFormatter
 *
 * @package srag\DataTableUI\OpencastObject\Implementation\Column\Formatter
 */
class CheckFormatter extends DefaultFormatter
{

    /**
     * @inheritDoc
     */
    public function formatRowCell(Format $format, $check, Column $image_path, RowData $row, string $table_id) : string
    {
        if ($check) {
            $image_path = ilUtil::getImagePath("icon_ok.svg");
        } else {
            $image_path = ilUtil::getImagePath("icon_not_ok.svg");
        }

        return self::output()->getHTML(self::dic()->ui()->factory()->image()->standard($image_path, ""));
    }
}
