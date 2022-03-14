<?php

namespace srag\DataTableUI\OpenCast\Implementation\Format\Browser;

use srag\DataTableUI\OpenCast\Component\Format\Browser\BrowserFormat;
use srag\DataTableUI\OpenCast\Component\Format\Browser\Factory as FactoryInterface;
use srag\DataTableUI\OpenCast\Component\Format\Browser\Filter\Factory as FilterFactoryInterface;
use srag\DataTableUI\OpenCast\Implementation\Format\Browser\Filter\Factory as FilterFactory;
use srag\DataTableUI\OpenCast\Implementation\Utils\DataTableUITrait;
use srag\DIC\OpenCast\DICTrait;

/**
 * Class Factory
 *
 * @package srag\DataTableUI\OpenCast\Implementation\Format\Browser
 */
class Factory implements FactoryInterface
{

    use DICTrait;
    use DataTableUITrait;

    /**
     * @var self|null
     */
    protected static $instance = null;


    /**
     * Factory constructor
     */
    private function __construct()
    {

    }


    /**
     * @return self
     */
    public static function getInstance() : self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
     * @inheritDoc
     */
    public function default() : BrowserFormat
    {
        return new DefaultBrowserFormat();
    }


    /**
     * @inheritDoc
     */
    public function filter() : FilterFactoryInterface
    {
        return FilterFactory::getInstance();
    }
}
