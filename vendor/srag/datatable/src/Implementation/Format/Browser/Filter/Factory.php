<?php

namespace srag\DataTableUI\OpencastObject\Implementation\Format\Browser\Filter;

use srag\CustomInputGUIs\OpencastObject\FormBuilder\FormBuilder as FormBuilderInterface;
use srag\DataTableUI\OpencastObject\Component\Format\Browser\BrowserFormat;
use srag\DataTableUI\OpencastObject\Component\Format\Browser\Filter\Factory as FactoryInterface;
use srag\DataTableUI\OpencastObject\Component\Settings\Settings;
use srag\DataTableUI\OpencastObject\Component\Table;
use srag\DataTableUI\OpencastObject\Implementation\Utils\DataTableUITrait;
use srag\DIC\OpencastObject\DICTrait;

/**
 * Class Factory
 *
 * @package srag\DataTableUI\OpencastObject\Implementation\Format\Browser\Filter
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
