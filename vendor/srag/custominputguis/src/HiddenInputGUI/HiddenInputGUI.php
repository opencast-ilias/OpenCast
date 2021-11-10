<?php

namespace srag\CustomInputGUIs\OpenCast\HiddenInputGUI;

use ilHiddenInputGUI;
use srag\CustomInputGUIs\OpenCast\Template\Template;
use srag\DIC\OpenCast\DICTrait;

/**
 * Class HiddenInputGUI
 *
 * @package srag\CustomInputGUIs\OpenCast\HiddenInputGUI
 */
class HiddenInputGUI extends ilHiddenInputGUI
{

    use DICTrait;

    /**
     * HiddenInputGUI constructor
     *
     * @param string $a_postvar
     */
    public function __construct(string $a_postvar = "")
    {
        parent::__construct($a_postvar);
    }


    /**
     * @return string
     */
    public function render() : string
    {
        $tpl = new Template("Services/Form/templates/default/tpl.property_form.html", true, true);

        $this->insert($tpl);

        return self::output()->getHTML($tpl);
    }
}
