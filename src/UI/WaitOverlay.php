<?php

declare(strict_types=1);

/**
 * @author Fabian Schmid <fabian@sr.solutions>
 */
class WaitOverlay
{
    private const BASE = './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/';

    /**
     * @var bool
     */
    private $init = false;

    /**
     * @var ilGlobalTemplateInterface
     */
    private $tpl;

    public function __construct(ilGlobalTemplateInterface $tpl)
    {
        $this->tpl = $tpl;
        $this->init();
    }

    private function init(): void
    {
        if ($this->init) {
            return;
        }
        $this->tpl->addJavaScript(
            self::BASE . 'js/opencast/dist/index.js'
        );
        $this->tpl->addCss(
            self::BASE . 'templates/default/waiter.css'
        );
        $this->tpl->addOnLoadCode('il.Opencast.UI.waitOverlay.init();');
        $this->init = true;
    }

    public function onUnload(): void
    {
        $this->tpl->addOnLoadCode(
            'window.onbeforeunload = function(){
                        il.Opencast.UI.waitOverlay.show();
                    };'
        );
    }

    public function onClick(string $dom_selector_string): void
    {
        $this->tpl->addOnLoadCode(
            'il.Opencast.UI.waitOverlay.addListener("' . $dom_selector_string . '");'
        );
    }

    public function onLinkClick(string $dom_selector_string_of_link): void
    {
        $this->tpl->addOnLoadCode(
            'il.Opencast.UI.waitOverlay.addLinkOverlay("' . $dom_selector_string_of_link . '");'
        );
    }

    public function onFormSubmit(string $dom_selector_string_of_input_or_form): void
    {
        $this->tpl->addOnLoadCode(
            'il.Opencast.UI.waitOverlay.onFormSubmit("' . $dom_selector_string_of_input_or_form . '");'
        );
    }

    public function onLoad(): void
    {
        $this->tpl->addOnLoadCode(
            'il.Opencast.UI.waitOverlay.show();'
        );
    }
}
