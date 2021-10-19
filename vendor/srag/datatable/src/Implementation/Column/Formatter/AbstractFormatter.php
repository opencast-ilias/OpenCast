<?php

namespace srag\DataTableUI\OpenCast\Implementation\Column\Formatter;

use srag\DataTableUI\OpenCast\Component\Column\Formatter\Formatter;
use srag\DataTableUI\OpenCast\Implementation\Utils\DataTableUITrait;
use srag\DIC\OpenCast\DICTrait;

/**
 * Class AbstractFormatter
 *
 * @package srag\DataTableUI\OpenCast\Implementation\Column\Formatter
 */
abstract class AbstractFormatter implements Formatter
{

    use DICTrait;
    use DataTableUITrait;

    /**
     * AbstractFormatter constructor
     */
    public function __construct()
    {

    }
}
