<?php

namespace srag\CustomInputGUIs\OpencastObject\UIInputComponentWrapperInputGUI;

use ILIAS\UI\Implementation\Component\Input\NameSource;

/**
 * Class UIInputComponentWrapperNameSource
 *
 * @package srag\CustomInputGUIs\OpencastObject\UIInputComponentWrapperInputGUI
 */
class UIInputComponentWrapperNameSource implements NameSource
{

    /**
     * @var string
     */
    protected $post_var;


    /**
     * UIInputComponentWrapperNameSource constructor
     *
     * @param string $post_var
     */
    public function __construct(string $post_var)
    {
        $this->post_var = $post_var;
    }


    /**
     * @inheritDoc
     */
    public function getNewName() : string
    {
        return $this->post_var;
    }
}
