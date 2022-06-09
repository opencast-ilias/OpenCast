<?php

namespace srag\DataTableUI\OpencastObject\Component\Format\Browser\Filter;

use srag\CustomInputGUIs\OpencastObject\FormBuilder\FormBuilder;
use srag\DataTableUI\OpencastObject\Component\Format\Browser\BrowserFormat;
use srag\DataTableUI\OpencastObject\Component\Settings\Settings;
use srag\DataTableUI\OpencastObject\Component\Table;

/**
 * Interface Factory
 *
 * @package srag\DataTableUI\OpencastObject\Component\Format\Browser\Filter
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
