<?php

namespace srag\DataTableUI\OpenCast\Implementation\Data\Row;

use srag\CustomInputGUIs\OpenCast\PropertyFormGUI\Items\Items;

/**
 * Class GetterRowData
 *
 * @package srag\DataTableUI\OpenCast\Implementation\Data\Row
 */
class GetterRowData extends AbstractRowData
{

    /**
     * @inheritDoc
     */
    public function __invoke(string $key)
    {
        return Items::getter($this->getOriginalData(), $key);
    }
}
