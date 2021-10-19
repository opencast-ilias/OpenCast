<?php

namespace srag\DataTableUI\OpenCast\Component\Data\Fetcher;

use srag\DataTableUI\OpenCast\Component\Data\Data;
use srag\DataTableUI\OpenCast\Component\Settings\Settings;
use srag\DataTableUI\OpenCast\Component\Table;

/**
 * Interface DataFetcher
 *
 * @package srag\DataTableUI\OpenCast\Component\Data\Fetcher
 */
interface DataFetcher
{

    /**
     * @param Settings $settings
     *
     * @return Data
     */
    public function fetchData(Settings $settings) : Data;


    /**
     * @param Table $component
     *
     * @return string
     */
    public function getNoDataText(Table $component) : string;


    /**
     * @return bool
     */
    public function isFetchDataNeedsFilterFirstSet() : bool;
}
