<?php

namespace srag\DataTableUI\OpencastObject\Implementation\Column\Formatter\Actions;

use srag\DataTableUI\OpencastObject\Component\Column\Formatter\Actions\ActionsFormatter;
use srag\DataTableUI\OpencastObject\Component\Column\Formatter\Actions\Factory as FactoryInterface;
use srag\DataTableUI\OpencastObject\Implementation\Utils\DataTableUITrait;
use srag\DIC\OpencastObject\DICTrait;

/**
 * Class Factory
 *
 * @package srag\DataTableUI\OpencastObject\Implementation\Column\Formatter\Actions
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
    public function actionsDropdown() : ActionsFormatter
    {
        return new ActionsDropdownFormatter();
    }


    /**
     * @inheritDoc
     */
    public function sort() : ActionsFormatter
    {
        return new SortFormatter();
    }
}
