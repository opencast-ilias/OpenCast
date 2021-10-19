<?php

namespace srag\CustomInputGUIs\OpenCast\FormBuilder;

use ILIAS\UI\Component\Input\Container\Form\Form;

/**
 * Interface FormBuilder
 *
 * @package srag\CustomInputGUIs\OpenCast\FormBuilder
 */
interface FormBuilder
{

    /**
     * @return Form
     */
    public function getForm() : Form;


    /**
     * @return string
     */
    public function render() : string;


    /**
     * @return bool
     */
    public function storeForm() : bool;
}
