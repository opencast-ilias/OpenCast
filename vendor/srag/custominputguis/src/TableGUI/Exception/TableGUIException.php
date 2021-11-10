<?php

namespace srag\CustomInputGUIs\OpenCast\TableGUI\Exception;

use ilException;

/**
 * Class TableGUIException
 *
 * @package srag\CustomInputGUIs\OpenCast\TableGUI\Exception
 *
 * @deprecated
 */
final class TableGUIException extends ilException
{

    /**
     * @var int
     *
     * @deprecated
     */
    const CODE_INVALID_FIELD = 1;


    /**
     * TableGUIException constructor
     *
     * @param string $message
     * @param int    $code
     *
     * @deprecated
     */
    public function __construct(string $message, int $code)
    {
        parent::__construct($message, $code);
    }
}
