<?php

namespace srag\DataTableUI\OpencastObject\Implementation\Data\Fetcher;

use srag\DataTableUI\OpencastObject\Component\Data\Fetcher\DataFetcher;
use srag\DataTableUI\OpencastObject\Component\Table;
use srag\DataTableUI\OpencastObject\Implementation\Utils\DataTableUITrait;
use srag\DIC\OpencastObject\DICTrait;

/**
 * Class AbstractDataFetcher
 *
 * @package srag\DataTableUI\OpencastObject\Implementation\Data\Fetcher
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
