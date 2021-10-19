<?php

namespace srag\DataTableUI\OpenCast\Implementation\Format\Browser\Filter;

use srag\CustomInputGUIs\OpenCast\FormBuilder\FormBuilder as FormBuilderInterface;
use srag\DataTableUI\OpenCast\Component\Format\Browser\BrowserFormat;
use srag\DataTableUI\OpenCast\Component\Format\Browser\Filter\Factory as FactoryInterface;
use srag\DataTableUI\OpenCast\Component\Settings\Settings;
use srag\DataTableUI\OpenCast\Component\Table;
use srag\DataTableUI\OpenCast\Implementation\Utils\DataTableUITrait;
use srag\DIC\OpenCast\DICTrait;

/**
 * Class Factory
 *
 * @package srag\DataTableUI\OpenCast\Implementation\Format\Browser\Filter
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
    public function formBuilder(BrowserFormat $parent, Table $component, Settings $settings) : FormBuilderInterface
    {
        return new FormBuilder($parent, $component, $settings);
    }
}
