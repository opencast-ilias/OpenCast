<?php

namespace srag\DataTableUI\OpencastObject\Implementation\Utils;

use srag\DataTableUI\OpencastObject\Component\Factory as FactoryInterface;
use srag\DataTableUI\OpencastObject\Implementation\Factory;

/**
 * Trait DataTableUITrait
 *
 * @package srag\DataTableUI\OpencastObject\Implementation\Utils
 */
trait DataTableUITrait
{

    /**
     * @return FactoryInterface
     */
    protected static function dataTableUI() : FactoryInterface
    {
        return Factory::getInstance();
    }
}
