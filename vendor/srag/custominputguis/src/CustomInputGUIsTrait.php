<?php

namespace srag\CustomInputGUIs\OpencastObject;

/**
 * Trait CustomInputGUIsTrait
 *
 * @package srag\CustomInputGUIs\OpencastObject
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
