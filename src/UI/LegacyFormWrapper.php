<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI;

use ilPropertyFormGUI;

/**
 * Wraps html in an ilPropertyFormGUI.
 * Necessary to use UIService's form in ILIAS' object creation (see ilObjOpencastGUI::initCreateForm).
 */
class LegacyFormWrapper extends ilPropertyFormGUI
{
    /**
     * @var string
     */
    private $html;

    /**
     * @noinspection MagicMethodsValidityInspection
     */
    public function __construct(string $html)
    {
        $this->html = $html;
        parent::__construct();
    }

    public function getHTML(): string
    {
        return $this->html;
    }
}
