<?php

namespace srag\DataTableUI\OpencastObject\Implementation\Data\Row;

use srag\CustomInputGUIs\OpencastObject\PropertyFormGUI\Items\Items;

/**
 * Class GetterRowData
 *
 * @package srag\DataTableUI\OpencastObject\Implementation\Data\Row
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
