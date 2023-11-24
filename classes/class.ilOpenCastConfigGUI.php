<?php

declare(strict_types=1);

/**
 * ilOpenCastConfigGUI
 *
 * @author             Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy ilOpenCastConfigGUI: ilObjComponentSettingsGUI
 */
class ilOpenCastConfigGUI extends ilPluginConfigGUI
{
    /**
     * @var \ilCtrl
     */
    private $ctrl;
    /**
     * @var \ilGlobalTemplateInterface
     */
    private $main_tpl;
    /**
     * @var \ilLanguage
     */
    private $language;
    /**
     * @var \ilTabsGUI
     */
    private $tabs;
    /**
     * @var \ILIAS\DI\HTTPServices
     */
    protected $http;

    public function __construct()
    {
        global $DIC;
        $this->ctrl = $DIC->ctrl();
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->language = $DIC->language();
        $this->tabs = $DIC->tabs();
        $this->http = $DIC->http();
    }

    public function executeCommand(): void
    {
        $get = $this->http->request()->getQueryParams();
        $this->ctrl->setParameterByClass("ilobjcomponentsettingsgui", "ctype", $get["ctype"]);
        $this->ctrl->setParameterByClass("ilobjcomponentsettingsgui", "cname", $get["cname"]);
        $this->ctrl->setParameterByClass("ilobjcomponentsettingsgui", "slot_id", $get["slot_id"]);
        $this->ctrl->setParameterByClass("ilobjcomponentsettingsgui", "plugin_id", $get["plugin_id"]);
        $this->ctrl->setParameterByClass("ilobjcomponentsettingsgui", "pname", $get["pname"]);

        $this->main_tpl->setTitle($this->language->txt("cmps_plugin") . ": " . $get["pname"]);
        $this->main_tpl->setDescription("");

        $this->tabs->clearTargets();

        if ($get["plugin_id"]) {
            $this->tabs->setBackTarget(
                $this->language->txt("cmps_plugin"),
                $this->ctrl->getLinkTargetByClass("ilobjcomponentsettingsgui", "showPlugin")
            );
        } else {
            $this->tabs->setBackTarget(
                $this->language->txt("cmps_plugins"),
                $this->ctrl->getLinkTargetByClass("ilobjcomponentsettingsgui", "listPlugins")
            );
        }

        $nextClass = $this->ctrl->getNextClass();

        if ($nextClass) {
            $a_gui_object = new xoctMainGUI();
            $this->ctrl->forwardCommand($a_gui_object);
        } else {
            $this->ctrl->redirectByClass(['xoctMainGUI', 'xoctConfGUI']);
        }
    }

    public function performCommand($cmd): void
    {
    }
}
