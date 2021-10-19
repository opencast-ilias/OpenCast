<?php

namespace srag\DataTableUI\OpenCast\Implementation\Utils;

use srag\DataTableUI\OpenCast\Component\Factory as FactoryInterface;
use srag\DataTableUI\OpenCast\Implementation\Factory;

/**
 * Trait DataTableUITrait
 *
 * @package srag\DataTableUI\OpenCast\Implementation\Utils
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
