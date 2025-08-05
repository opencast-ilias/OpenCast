<?php

declare(strict_types=1);

namespace srag\Plugins\Opencast\UI;

use ilPropertyFormGUI;
use Psr\Http\Message\ServerRequestInterface;
use ILIAS\HTTP\SuperGlobalDropInReplacementTest;
use ILIAS\HTTP\Wrapper\SuperGlobalDropInReplacement;

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

    public function withRequest(ServerRequestInterface $request): self
    {
        $this->setValuesByPost();
        return $this;
    }

    public function getData(): array
    {
        foreach ($this->getItems() as $item) {

        }


        /**
         * @var SuperGlobalDropInReplacement $_POST
         */
$data = [];
        foreach ($_POST as $key => $value) {
            $data[$key] = $value;
        }



        return $data;

        $data = [];
        foreach ($this->getItems() as $item) {
            $data[$item->getPostVar()] = $item->getValue();
        }

        return $data;
    }
}
