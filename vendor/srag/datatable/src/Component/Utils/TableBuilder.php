<?php

namespace srag\DataTableUI\OpenCast\Component\Utils;

use srag\DataTableUI\OpenCast\Component\Table;

/**
 * Interface TableBuilder
 *
 * @package srag\DataTableUI\OpenCast\Component\Utils
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
