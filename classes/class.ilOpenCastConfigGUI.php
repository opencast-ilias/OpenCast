<?php

/**
 * ilOpenCastConfigGUI
 *
 * @author             Fabian Schmid <fs@studer-raimann.ch>
 *
 * @ilCtrl_IsCalledBy  ilOpenCastConfigGUI: ilObjComponentSettingsGUIs
 */
class ilOpenCastConfigGUI extends ilPluginConfigGUI
{
    /**
     * @var \ilCtrlInterface
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

    public function __construct()
    {
        global $DIC;
        $this->ctrl = $DIC->ctrl();
        $this->main_tpl = $DIC->ui()->mainTemplate();
        $this->language = $DIC->language();
        $this->tabs = $DIC->tabs();
    }

    public function executeCommand(): void
    {
        $this->ctrl->setParameterByClass("ilobjcomponentsettingsgui", "ctype", $_GET["ctype"]);
        $this->ctrl->setParameterByClass("ilobjcomponentsettingsgui", "cname", $_GET["cname"]);
        $this->ctrl->setParameterByClass("ilobjcomponentsettingsgui", "slot_id", $_GET["slot_id"]);
        $this->ctrl->setParameterByClass("ilobjcomponentsettingsgui", "plugin_id", $_GET["plugin_id"]);
        $this->ctrl->setParameterByClass("ilobjcomponentsettingsgui", "pname", $_GET["pname"]);

        $this->main_tpl->setTitle($this->language->txt("cmps_plugin") . ": " . $_GET["pname"]);
        $this->main_tpl->setDescription("");

        $this->tabs->clearTargets();

        if ($_GET["plugin_id"]) {
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
