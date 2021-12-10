<?php

namespace srag\Plugins\Opencast\UI;

use ilPropertyFormGUI;

class LegacyFormWrapper extends ilPropertyFormGUI
{
    /**
     * @var string
     */
    private $html;

    /**
     * @param string $html
     */
    public function __construct(string $html)
    {
        $this->html = $html;
    }

    public function getHTML()
    {
        return $this->html;
    }

}