<?php

namespace srag\DataTableUI\OpencastObject\Component\Format\Browser;

use srag\DataTableUI\OpencastObject\Component\Format\Browser\Filter\Factory as FilterFactory;

/**
 * Interface Factory
 *
 * @package srag\DataTableUI\OpencastObject\Component\Format\Browser
 */
interface Factory
{

    /**
     * @return BrowserFormat
     */
    public function default() : BrowserFormat;


    /**
     * @return FilterFactory
     */
    public function filter() : FilterFactory;
}
