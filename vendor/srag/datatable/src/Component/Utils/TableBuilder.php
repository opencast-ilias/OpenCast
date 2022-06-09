<?php

namespace srag\DataTableUI\OpencastObject\Component\Utils;

use srag\DataTableUI\OpencastObject\Component\Table;

/**
 * Interface TableBuilder
 *
 * @package srag\DataTableUI\OpencastObject\Component\Utils
 */
interface TableBuilder
{

    /**
     * @return Table
     */
    public function getTable() : Table;


    /**
     * @return string
     */
    public function render() : string;
}
