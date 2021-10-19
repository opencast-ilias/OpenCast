<?php

use srag\DIC\OpenCast\DICTrait;

/**
 * @ilCtrl_IsCalledBy xoctMetadataConfigRouterGUI : xoctMainGUI
 */
class xoctMetadataConfigRouterGUI
{
    use DICTrait;
    const PLUGIN_CLASS_NAME = ilOpenCastPlugin::class;

    const SUBTAB_EVENTS = 'events';
    const SUBTAB_SERIES = 'series';


    public function executeCommand()
    {
        $nextClass = self::dic()->ctrl()->getNextClass();

        switch ($nextClass) {
            case strtolower(xoctSeriesMetadataConfigGUI::class):
                $this->setSubTabs(self::SUBTAB_SERIES);
                $gui = new xoctSeriesMetadataConfigGUI();
                self::dic()->ctrl()->forwardCommand($gui);
                break;
            case strtolower(xoctEventMetadataConfigGUI::class):
            default:
                $this->setSubTabs(self::SUBTAB_EVENTS);
                $gui = new xoctEventMetadataConfigGUI();
                self::dic()->ctrl()->forwardCommand($gui);
                break;
        }
    }

    private function setSubTabs(string $active_subtab)
    {
        self::dic()->tabs()->addSubTab(
            self::SUBTAB_EVENTS,
            self::plugin()->translate('subtab_' . self::SUBTAB_EVENTS),
            self::dic()->ctrl()->getLinkTargetByClass(xoctEventMetadataConfigGUI::class)
        );
        self::dic()->tabs()->addSubTab(
            self::SUBTAB_SERIES,
            self::plugin()->translate('subtab_' . self::SUBTAB_SERIES),
            self::dic()->ctrl()->getLinkTargetByClass(xoctSeriesMetadataConfigGUI::class)
        );
        self::dic()->tabs()->activateSubTab($active_subtab);
    }

}