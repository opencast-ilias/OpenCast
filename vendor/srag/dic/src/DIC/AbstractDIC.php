<?php

namespace srag\DIC\OpencastObject\DIC;

use ILIAS\DI\Container;
use srag\DIC\OpencastObject\Database\DatabaseDetector;
use srag\DIC\OpencastObject\Database\DatabaseInterface;

/**
 * Class AbstractDIC
 *
 * @package srag\DIC\OpencastObject\DIC
 */
abstract class AbstractDIC implements DICInterface
{

    /**
     * @var Container
     */
    protected $dic;


    /**
     * @inheritDoc
     */
    public function __construct(Container &$dic)
    {
        $this->dic = &$dic;
    }


    /**
     * @inheritDoc
     */
    public function database() : DatabaseInterface
    {
        return DatabaseDetector::getInstance($this->databaseCore());
    }
}
