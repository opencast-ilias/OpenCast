<?php

namespace srag\CustomInputGUIs\OpenCast;

/**
 * Trait CustomInputGUIsTrait
 *
 * @package srag\CustomInputGUIs\OpenCast
 */
trait CustomInputGUIsTrait
{

    /**
     * @return CustomInputGUIs
     */
    protected static final function customInputGUIs() : CustomInputGUIs
    {
        return CustomInputGUIs::getInstance();
    }
}
