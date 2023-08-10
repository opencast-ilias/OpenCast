<?php

use srag\DIC\OpenCast\DICTrait;

/**
 * Class xoctWaiterGUI
 *
 * @author  Fabian Schmid <fs@studer-raimann.ch>
 * @version 1.0.0
 */
class xoctWaiterGUI
{
    public const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

    /**
     * @var bool
     */
    protected static $init = false;
    /**
     * @var bool
     */
    protected static $init_js = false;

    /**
     *
     */
    public static function loadLib()
    {
        global $DIC;
        $main_tpl = $DIC->ui()->mainTemplate();
        if (!self::$init) {
            $main_tpl->addJavaScript(
                './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/waiter.min.js'
            );
            $main_tpl->addCss(
                './Customizing/global/plugins/Services/Repository/RepositoryObject/OpenCast/templates/default/waiter.css'
            );
            self::$init = true;
        }
    }

    /**
     * @param string $type
     */
    public static function initJS($type = 'waiter')
    {
        global $DIC;
        $main_tpl = $DIC->ui()->mainTemplate();
        self::loadLib();
        if (!self::$init_js) {
            $code = 'xoctWaiter.init(\'' . $type . '\');';
            $main_tpl->addOnLoadCode($code);
            self::$init_js = true;
        }
    }

    /**
     * @param $dom_selector_string
     */
    public static function addListener($dom_selector_string)
    {
        global $DIC;
        $main_tpl = $DIC->ui()->mainTemplate();
        $code = 'xoctWaiter.addListener("' . $dom_selector_string . '");';
        $main_tpl->addOnLoadCode($code);
    }

    /**
     * @param $dom_selector_string
     */
    public static function addLinkOverlay($dom_selector_string)
    {
        global $DIC;
        $main_tpl = $DIC->ui()->mainTemplate();
        $code = 'xoctWaiter.addLinkOverlay("' . $dom_selector_string . '");';
        $main_tpl->addOnLoadCode($code);
    }

    public static function show()
    {
        global $DIC;
        $main_tpl = $DIC->ui()->mainTemplate();
        self::initJS();
        $code = 'xoctWaiter.show();';
        $main_tpl->addOnLoadCode($code);
    }
}
