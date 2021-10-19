<?php

namespace srag\DataTableUI\OpenCast\Implementation\Data\Fetcher;

use srag\DataTableUI\OpenCast\Component\Data\Fetcher\DataFetcher;
use srag\DataTableUI\OpenCast\Component\Table;
use srag\DataTableUI\OpenCast\Implementation\Utils\DataTableUITrait;
use srag\DIC\OpenCast\DICTrait;

/**
 * Class AbstractDataFetcher
 *
 * @package srag\DataTableUI\OpenCast\Implementation\Data\Fetcher
 */
abstract class AbstractDataFetcher implements DataFetcher
{

    use DICTrait;
    use DataTableUITrait;

    /**
     * AbstractDataFetcher constructor
     */
    public function __construct()
    {

    }


    /**
     * @inheritDoc
     */
    public function getNoDataText(Table $component) : string
    {
        return $component->getPlugin()->translate("no_data", Table::LANG_MODULE);
    }


    /**
     * @inheritDoc
     */
    public function isFetchDataNeedsFilterFirstSet() : bool
    {
        return false;
    }
}
