<?php

namespace srag\DataTableUI\OpencastObject\Component\Data\Fetcher;

use srag\DataTableUI\OpencastObject\Component\Data\Data;
use srag\DataTableUI\OpencastObject\Component\Settings\Settings;
use srag\DataTableUI\OpencastObject\Component\Table;

/**
 * Interface DataFetcher
 *
 * @package srag\DataTableUI\OpencastObject\Component\Data\Fetcher
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
