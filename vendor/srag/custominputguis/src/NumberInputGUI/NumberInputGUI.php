<?php

namespace srag\CustomInputGUIs\OpenCast\NumberInputGUI;

use ilNumberInputGUI;
use ilTableFilterItem;
use ilToolbarItem;
use srag\DIC\OpenCast\DICTrait;

/**
 * Class NumberInputGUI
 *
 * @package srag\CustomInputGUIs\OpenCast\NumberInputGUI
 */
class NumberInputGUI extends ilNumberInputGUI implements ilTableFilterItem, ilToolbarItem
{

    use DICTrait;

    /**
     * @inheritDoc
     */
    public function getTableFilterHTML() : string
    {
        return $this->render();
    }


    /**
     * @inheritDoc
     */
    public function getToolbarHTML() : string
    {
        return $this->render();
    }
}
