<?php

namespace srag\DataTableUI\OpenCast\Component\Format\Browser\Filter;

use srag\CustomInputGUIs\OpenCast\FormBuilder\FormBuilder;
use srag\DataTableUI\OpenCast\Component\Format\Browser\BrowserFormat;
use srag\DataTableUI\OpenCast\Component\Settings\Settings;
use srag\DataTableUI\OpenCast\Component\Table;

/**
 * Interface Factory
 *
 * @package srag\DataTableUI\OpenCast\Component\Format\Browser\Filter
 */
interface Factory
{

    /**
     * @param BrowserFormat $parent
     * @param Table         $component
     * @param Settings      $settings
     *
     * @return FormBuilder
     */
    public function formBuilder(BrowserFormat $parent, Table $component, Settings $settings) : FormBuilder;
}
