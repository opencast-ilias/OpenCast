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
     * @noinspection MagicMethodsValidityInspection
     */
    public function __construct(private readonly string $html)
    {
        parent::__construct();
    }

    public function getHTML(): string
    {
        return $this->html;
    }
}
