<?php

namespace srag\DataTableUI\OpencastObject\Component\Column\Formatter\Actions;

/**
 * Interface Factory
 *
 * @package srag\DataTableUI\OpencastObject\Component\Column\Formatter\Actions
 */
interface Factory
{

    /**
     * @return ActionsFormatter
     */
    public function actionsDropdown() : ActionsFormatter;


    /**
     * @return ActionsFormatter
     */
    public function sort() : ActionsFormatter;
}
