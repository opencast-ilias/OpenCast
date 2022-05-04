<?php

namespace srag\DataTableUI\OpencastObject\Component\Data;

use srag\DataTableUI\OpencastObject\Component\Data\Fetcher\Factory as FetcherFactory;
use srag\DataTableUI\OpencastObject\Component\Data\Row\Factory as RowFactory;
use srag\DataTableUI\OpencastObject\Component\Data\Row\RowData;

/**
 * Interface Factory
 *
 * @package srag\DataTableUI\OpencastObject\Component\Data
 */
interface Factory
{

    /**
     * @param RowData[] $data
     * @param int       $max_count
     *
     * @return Data
     */
    public function data(array $data, int $max_count) : Data;


    /**
     * @return FetcherFactory
     */
    public function fetcher() : FetcherFactory;


    /**
     * @return RowFactory
     */
    public function row() : RowFactory;
}
